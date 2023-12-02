<?php

declare(strict_types=1);

namespace Restock\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\ActionLog;
use Restock\Entity\Group;
use Restock\Entity\GroupMember;
use Restock\Entity\Invite;
use Restock\Entity\Item;
use Restock\Entity\User;
use Restock\ActionLogger;
use Restock\PResponse;

class GroupController
{
    private EntityManager $entityManager;
    private User $user;

    public function __construct(EntityManager $entityManager, User $user)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    /**
     * Fetch user's details.
     *
     * GET /group
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * Response:
     * [
     *  {
     *   "id": "2",
     *   "name": "my pantry",
     *  },
     *  ...
     * ]
     *
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function getUserGroups(ServerRequestInterface $request)
    {
        $groups = array_map(fn(GroupMember $gm): Group => $gm->getGroup(),
            $this->entityManager->getRepository(GroupMember::class)->findBy(['user' => $this->user->getId()])
        );
        return PResponse::ok(array_map(fn(Group $g) => $g->toArray(), $groups));
    }

    /**
     * Fetch group's details.
     *
     * GET /group/{group_id:number}
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * Response:
     * {
     *  "id": "2",
     *  "name": "my pantry",
     *  "group_members": [
     *      {
     *          "id": "4",
     *          "group_id": "2",
     *          "user_id": "22",
     *          "role": "member"
     *      },
     *      ...
     *  ],
     *  "items": [
     *        {
     *            "id": "15",
     *            "group_id": "2",
     *            "name": "ketchup",
     *            "description": "sugary tomato paste",
     *            "category": "deafult;#000000",
     *            "pantry_quantity": "62",
     *            "minimum_threshold": "40",
     *            "auto_add_to_shopping_list": "true",
     *            "shopping_list_quantity": "0",
     *            "dont_add_to_pantry_on_purchase": "false"
     *        },
     *        ...
     *    ],
     *  "action_logs": [
     *      {
     *          "id": "102",
     *          "group_id": "2",
     *          "log_message": "Group The Pantry Room created.",
     *          "timestamp": {I've no clue}
     *  ]
     * }
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function getGroupDetails(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';

        if (empty($group_id)) {
            return PResponse::badRequest('Required parameter missing.');
        }

        $user = $this->user;

        $role = $user->getMemberDetails()->findFirst(
            fn($_, GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            PResponse::forbidden('You do not have permission to view this group, or the group does not exist.');
        }
        /** @var Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);

        return PResponse::ok([
            ...$group->toArray(),
            "group_members" => array_map(fn(GroupMember $groupMember): array => $groupMember->toArray(),
                $group->getGroupMembers()->toArray()),
            "items" => array_map(fn(Item $item): array => $item->toArray(), $group->getItems()->toArray()),
            "action_logs" => array_map(fn(ActionLog $actionLog): array => $actionLog->toArray(),
                $group->getHistory()->toArray())
        ]);
    }

    /**
     * Register a new group
     *
     * POST /group
     *  Accept: application/json
     *  X-RestockUserApiToken: {token}
     *  X-RestockApiToken: anything
     *  Content:
     *   name={new group name}
     *
     * Response:
     * {
     *  "id": "2",
     *  "name" "The Pantry Room"
     * }
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createGroup(ServerRequestInterface $request): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $owner = $this->user;
        $name = $request->getParsedBody()['name'] ?? '';

        // TODO: Duplicate group name error
        $group = new Group($name, $owner);
        $this->entityManager->persist($group);
        $this->entityManager->flush();
        $actionLogger->createActionLog($group, 'Group ' . $group->getName() . ' created');

        return PResponse::created($group->toArray());
    }

    /**
     * Update group
     *
     *  PUT /group/{group_id:number}
     *  Accept: application/json
     *  Content-Type: application/json
     *  X-RestockApiToken: anything
     *  X-RestockUserApiToken: {token}
     *  Content:
     *   {
     *       "name": "my new group's name!",
     *   }
     *
     * Response body:
     * {
     *   "id": "2",
     *   "name" "Buttery"
     * }
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateGroup(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $group_id = $args['group_id'] ?? '';
        $data = json_decode($request->getBody()->getContents(), true);
        $name = $data['name'] ?? '';

        if ($group_id === '') {
            return PResponse::badRequest('Required parameter missing.');
        }
        if ($name === '') {
            return PResponse::badRequest('Group name must not be empty.');
        }
        if (!is_string($name)) {
            return PResponse::badRequest('Group name must be a string.');
        }
        if (strlen($name) > 100 ) {
            return Presponse::badRequest('Group name is too long.');
        }

        $user = $this->user;

        $role = $user->getMemberDetails()->findFirst(
            fn($_, GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return PResponse::badRequest('You are not a member of this group, or the group does not exist.');
        }

        if ($role != GroupMember::OWNER) {
            return PResponse::forbidden('You do not have permission to update this group.');
        }

        /** @var Group $group */
        if ($group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(
            ['id' => $group_id]
        )) {
            $group->setName($name);
            $this->entityManager->persist($group);
            $this->entityManager->flush($group);
            $actionLogger->createActionLog($group, 'Group ' . $group->getName() . ' updated');

            return PResponse::ok($group->toArray());
        }

        return PResponse::serverErr('Error updating group.');
    }

    /**
     * Delete a group.
     *
     *  DELETE /group/{group_id:number}
     *  Accept: application/json
     *  X-RestockApiToken: anything
     *  X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteGroup(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';

        $user = $this->user;

        $role = $user->getMemberDetails()->findFirst(
            fn($_, GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return PResponse::forbidden('You are not a member of this group, or the group does not exist.');
        }

        if ($role != GroupMember::OWNER) {
            return PResponse::forbidden('You do not have permission to delete this group.');
        }

        /** @var Group $group */
        if ($group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(
            ['id' => $group_id]
        )) {
            $this->entityManager->remove($group);
            $this->entityManager->flush($group);

            return PResponse::ok();
        }

        return PResponse::serverErr('Error deleting group.');
    }

    public function getGroupMemberDetails(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function addGroupMember(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $group_id = $args['group_id'] ?? '';
        $user_id = $request->getParsedBody()['user_id'] ?? '';
        $new_role = $request->getParsedBody()['role'] ?? '';
        $user = $this->user;

        if (empty($group_id) || empty($user_id) || !is_string($user_id) || empty($new_role) || !is_string($new_role)) {
            return PResponse::badRequest('Required parameter missing.');
        }

        if ($new_role == GroupMember::OWNER) {
            return PResponse::badRequest('There can only be one owner of a group.');
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return PResponse::forbidden('You are not a member of this group, or the group does not exist.');
        }

        if ($role != GroupMember::OWNER) {
            return PResponse::forbidden('You do not have permission to add members to this group.');
        }

        /** @var Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);
        /** @var User $adding_user */
        $adding_user = $this->entityManager->getRepository('\Restock\Entity\User')->findOneBy(['id' => $user_id]);
        $group_member = new GroupMember($group, $adding_user);

        // TODO: Duplicate group member entries should not be possible. A joint unique constraint can be added to the database for this.
        try {
            $this->entityManager->persist($group_member);
            $this->entityManager->flush($group_member);
            $actionLogger->createActionLog($group, $user->getName() . ' added ' . $adding_user->getName() . ' to the group');
        } catch (\InvalidArgumentException) {
            // TODO: Generic exception should ideally be replaced with a specific one to catch for this situation
            return PResponse::badRequest('Invalid role for user.');
        }

        return PResponse::created($group_member->toArray());
    }

    /**
     * Change group member's role or set new owner
     *
     * PUT /group/{group_id:number}/member/{user_id:number}
     * Accept: application/json
     * Content-Type: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     * Content:
     *  {
     *      "role": "my new group's name!",
     *  }
     *
     * Response body:
     * {
     *   "id": "2",
     *   "name" "Buttery"
     * }
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateGroupMember(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $group_id = $args['group_id'] ?? '';
        $user_id = $args['user_id'] ?? '';
        $data = json_decode($request->getBody()->getContents(), true);
        $new_role = $data['role'] ?? '';
        $user = $this->user;

        // This is a "fix" to prevent an owner from demoting themselves and thus breaking the group.
        if ($user->getId() == $user_id) {
            return PResponse::badRequest('You cannot change your own group role.');
        }

        if (empty($group_id) || empty($user_id) || empty($new_role) || !is_string($new_role)) {
            return PResponse::badRequest('Required parameter missing.');
        }

        /** @var GroupMember $this_group_member - the group member making changes */
        $this_group_member = $this->entityManager->getRepository('\Restock\Entity\GroupMember')->findOneBy(
            ['group' => $group_id, 'user' => $user->getId()]
        );
        if (!$this_group_member) {
            return PResponse::forbidden('You are not a member of this group, or the group does not exist.');
        }

        /** @var GroupMember $that_group_member - the group member being changed */
        $that_group_member = $this->entityManager->getRepository('\Restock\Entity\GroupMember')->findOneBy(
            ['group' => $group_id, 'user' => $user_id]
        );
        if (!$that_group_member) {
            // User is not a member of this group
            return PResponse::forbidden('Target is not a member of this group, or the group does not exist.');
        }

        // Ensure the user can only change the role of users with a lesser role
        if (!$this_group_member->isHigherRoleThan($that_group_member)) {
            return PResponse::forbidden('You do not have permission to assign this role.');
        }

        try {
            $that_group_member->setRole($new_role);
            if ($that_group_member->getRole() == GroupMember::OWNER) {
                // Only the current owner can assign a new owner
                if ($this_group_member->getRole() !== GroupMember::OWNER) {
                    return PResponse::forbidden('You do not have permission to assign a different owner.');
                }

                // Demote the current owner to a member
                try {
                    $this_group_member->setRole(GroupMember::MEMBER);
                    $this->entityManager->persist($this_group_member);
                } catch (ORMException) {
                    return PResponse::serverErr('Failed to update database.');
                }
            }
        } catch (\InvalidArgumentException) {
            return PResponse::badRequest('Invalid role for group member.');
        }

        try {
            $this->entityManager->persist($that_group_member);
            $this->entityManager->flush();
            $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);
            if ($new_role == GroupMember::OWNER) {
                //When change in ownership occurs
                $actionLogger->createActionLog($group, $that_group_member->getUser()->getName() . ' is now the owner of group ' . $group->getName());
            } else {
                //User is assigned a new role
                $actionLogger->createActionLog($group, $user->getName() . ' assigned ' . $new_role . ' role to ' . $that_group_member->getUser()->getName());
            }
            return PResponse::ok($that_group_member->toArray());
        } catch (ORMException $e) {
            return PResponse::serverErr('Error updating group member.');
        }
    }

    /**
     * Delete a user from a group.
     *
     *  DELETE /group/{group_id:number}/member/{user_id:number}
     *  Accept: application/json
     *  X-RestockApiToken: anything
     *  X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteGroupMember(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $group_id = $args['group_id'] ?? '';
        $user_id = $args['user_id'] ?? '';
        $user = $this->user;

        if (empty($group_id) || empty($user_id)) {
            return PResponse::badRequest('Required parameter missing.');
        }

        /** @var GroupMember $this_group_member - the group member making changes */
        $this_group_member = $this->entityManager->getRepository('\Restock\Entity\GroupMember')->findOneBy(
            ['group' => $group_id, 'user' => $user->getId()]
        );
        if (!$this_group_member) {
            return PResponse::forbidden('You are not a member of this group, or the group does not exist.');
        }

        if ($user_id == $user->getId()) {
            // User is removing themself from the group
            if ($this_group_member->getRole() === GroupMember::OWNER) {
                // Owner cannot remove themselves
                return PResponse::forbidden('Owners cannot remove themselves from the group.');
            }

            try {
                $this->entityManager->remove($this_group_member);
                $this->entityManager->flush();
                return PResponse::ok();
            } catch (ORMException) {
                return PResponse::serverErr('Failed to update database.');
            }
        }

        /** @var GroupMember $that_group_member - the group member being changed */
        $that_group_member = $this->entityManager->getRepository('\Restock\Entity\GroupMember')->findOneBy(
            ['group' => $group_id, 'user' => $user_id]
        );
        if (!$that_group_member) {
            // User is not a member of this group
            return PResponse::forbidden('Target is not a member of this group, or the group does not exist.');
        }

        if ($this_group_member->getRole() != GroupMember::OWNER) {
            return PResponse::forbidden('You do not have permission to remove members from this group.');
        }

        // Ensure the user can only delete users with a lesser role
        if (!$this_group_member->isHigherRoleThan($that_group_member)) {
            return PResponse::forbidden('You do not have permission to remove this member.');
        }

        try {
            $this->entityManager->remove($that_group_member);
            $this->entityManager->flush($that_group_member);
            $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);
            if ($user_id == $user->getId()) {
                // User is removing themselves from the group
                $actionLogger->createActionLog($group, $user->getName() . ' left the group');
            } else {
                // User is getting kicked by someone else
                $actionLogger->createActionLog($group, $user->getName() . ' removed ' . $that_group_member->getUser()->getName() . ' from the group');
            }
            return PResponse::ok();
        } catch (ORMException) {
            return PResponse::serverErr('Error removing member from group.');
        }
    }

    public function getGroupMembers(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';
        $user = $this->user;

        if (empty($group_id)) {
            return PResponse::badRequest('Required parameter missing.');
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return PResponse::forbidden('You are not a member of this group, or the group does not exist.');
        }

        /** @var Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);
        /** @var GroupMember[] $members */
        $members = $group->getGroupMembers();

        $result = array();
        foreach ($members as $member) {
            $role = $this->entityManager->createQueryBuilder()
                ->select('gm.role')
                ->from('\Restock\Entity\GroupMember', 'gm')
                ->where('gm.user = :user_id')
                ->andWhere('gm.group = :group_id')
                ->setParameter(':user_id', $member->getUser()->getId())
                ->setParameter(':group_id', $group_id)
                ->getQuery()->execute();

            $result[] = [
                'id' => $member->getId(),
                'name' => $member->getUser()->getName(),
                'role' => $role[0]['role'],
            ];
        }

        return PResponse::ok($group->getGroupMembers()->toArray());
    }

    /**
     * Create an invitation to a group
     *
     * POST /group/{group_id:number}/invite
     * Accept: application/json
     * X-RestockUserApiToken: {token}
     * X-RestockApiToken: anything
     *
     * Response:
     * {
     *  "id": "17",
     *  "group_id",
     *  "code": "VSWfT4V3pnSyRFY8F4gz2bdM"
     * }
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createGroupInvite(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';
        $user = $this->user;

        if (empty($group_id)) {
            return PResponse::badRequest('Required parameter missing.');
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return PResponse::forbidden('You are not a member of this group, or the group does not exist.');
        }

        // TODO: Should other roles be allowed to manage invites?
        if ($role != GroupMember::OWNER) {
            return PResponse::forbidden('You do not have permission to create invites for this group.');
        }

        /** @var Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);
        $invite = new Invite($group);

        $this->entityManager->persist($invite);
        $this->entityManager->flush($invite);

        return PResponse::created($invite->toArray());
    }

    /**
     * Fetch unclaimed invitations
     *
     * GET /group/{group_id:number}/invite
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * Response:
     * {
     *  [
     *      {
     *          "id": "17",
     *          "group_id",
     *          "code": "VSWfT4V3pnSyRFY8F4gz2bdM"
     *      },
     *      ...
     *  ]
     * }
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function listGroupInvites(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';
        $user = $this->user;

        if (empty($group_id)) {
            return PResponse::badRequest('Required parameter missing.');
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return PResponse::forbidden('You are not a member of this group, or the group does not exist.');
        }

        if ($role != GroupMember::OWNER) {
            return PResponse::forbidden('You do not have permission to list invites for this group.');
        }

        /** @var Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);

        return PResponse::ok($group->getInvites()->toArray());
    }

    /**
     * Delete an unclaimed invitation.
     *
     * DELETE /group/{group_id:number}/invite/{invite_id:number}
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteGroupInvite(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';
        $invite_id = $args['invite_id'] ?? '';
        $user = $this->user;

        if (empty($group_id)) {
            return PResponse::badRequest('Required parameter missing.');
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return PResponse::forbidden('You are not a member of this group, or the group does not exist.');
        }

        if ($role != GroupMember::OWNER) {
            return PResponse::forbidden('You do not have permission to delete invites for this group.');
        }

        /** @var Invite $invite */
        $invite = $this->entityManager->getRepository('\Restock\Entity\Invite')->findOneBy(['id' => $invite_id]);

        if ($invite === null) {
            return PResponse::notFound();
        }

        $this->entityManager->remove($invite);
        $this->entityManager->flush($invite);

        return PResponse::ok();
    }

    /**
     * Check if an invitation exists
     *
     * GET /invite/{code:string}
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * Response:
     *  {
     *    "id": "2",
     *    "name" "Buttery"
     *  }
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function getGroupInviteDetails(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $code = $args['code'] ?? '';

        /** @var Invite $invite */
        $invite = $this->entityManager->getRepository('\Restock\Entity\Invite')->findOneBy(['code' => $code]);

        if ($invite === null) {
            return PResponse::notFound();
        }

        $group = $invite->getGroup();

        return PResponse::ok($group->toArray());
    }


    /**
     * Accept an invitation
     *
     * POST /invite/{code:string}
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * Response:
     *  {
     *    "id": "2",
     *    "name" "Buttery"
     *  }
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function acceptGroupInvite(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $actionLogger = new ActionLogger($this->entityManager);
        $code = $args['code'] ?? '';

        /** @var Invite $invite */
        $invite = $this->entityManager->getRepository('\Restock\Entity\Invite')->findOneBy(['code' => $code]);

        if ($invite === null) {
            return PResponse::notFound();
        }

        /** @var Group $group */
        $group = $invite->getGroup();
        $group_member = new GroupMember($group, $this->user);

        $this->entityManager->persist($group_member);
        $this->entityManager->remove($invite);
        $this->entityManager->flush();
        $actionLogger->createActionLog($group, $this->user->getName() . ' has joined the group');

        return PResponse::created($group_member->toArray());
    }
}
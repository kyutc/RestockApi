<?php

declare(strict_types=1);

namespace Restock\Controller;

use Doctrine\ORM\EntityManager;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Restock\Entity\User;

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
     * Fetch group's details.
     *
     * GET /group/{group_id:number}
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function getGroupDetails(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';

        if (empty($group_id)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        $user = $this->user;

        $role = $user->getMemberDetails()->findFirst(
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to view this group, or the group does not exist.'
            ],
                403
            );
        }
        /** @var \Restock\Entity\Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);

        return new JsonResponse([
            'result' => 'success',
            'data' => "{$group}"
        ],
            200
        );
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
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createGroup(ServerRequestInterface $request): ResponseInterface
    {
        $owner = $this->user;
        $name = $request->getParsedBody()['name'] ?? '';

        // TODO: Duplicate group name error
        $group = new \Restock\Entity\Group($name, $owner);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return new JsonResponse([
            'result' => 'success',
            'message' => 'Group has been created.',
            'data' => "{$group}"
        ],
            201
        );
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
     *
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
        $group_id = $args['group_id'] ?? '';
        $data = json_decode($request->getBody()->getContents(), true);
        $name = $data['name'] ?? '';

        if (empty($group_id) || empty($name) || !is_string($name)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        $user = $this->user;

        $role = $user->getMemberDetails()->findFirst(
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        if ($role != \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to update this group.'
            ],
                403
            );
        }

        /** @var \Restock\Entity\Group $group */
        if ($group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(
            ['id' => $group_id]
        )) {
            $group->setName($name);
            $this->entityManager->persist($group);
            $this->entityManager->flush($group);

            return new JsonResponse([
                'result' => 'success',
                'message' => 'Group has been updated.',
                'data' => "{$group}"
            ],
                200
            );
        }

        return new JsonResponse([
            'result' => 'error',
            'message' => 'Error updating group.'
        ],
            500
        );
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
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        if ($role != \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to delete this group.'
            ],
                403
            );
        }

        /** @var \Restock\Entity\Group $group */
        if ($group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(
            ['id' => $group_id]
        )) {
            $this->entityManager->remove($group);
            $this->entityManager->flush($group);

            return new JsonResponse([
                'result' => 'success',
                'message' => 'Group has been deleted.'
            ],
                200
            );
        }

        return new JsonResponse([
            'result' => 'error',
            'message' => 'Error deleting group.'
        ],
            500
        );
    }

    public function getGroupMemberDetails(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function addGroupMember(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';
        $user_id = $request->getParsedBody()['user_id'] ?? '';
        $new_role = $request->getParsedBody()['role'] ?? '';
        $user = $this->user;

        if (empty($group_id) || empty($user_id) || !is_string($user_id) || empty($new_role) || !is_string($new_role)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        if ($new_role == \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'There can only be one owner of a group.'
            ],
                400
            );
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        if ($role != \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to add members to this group.'
            ],
                403
            );
        }

        /** @var \Restock\Entity\Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);
        /** @var \Restock\Entity\User $adding_user */
        $adding_user = $this->entityManager->getRepository('\Restock\Entity\User')->findOneBy(['id' => $user_id]);
        $group_member = new \Restock\Entity\GroupMember($group, $adding_user);

        // TODO: Duplicate group member entries should not be possible. A joint unique constraint can be added to the database for this.
        try {
            $this->entityManager->persist($group_member);
            $this->entityManager->flush($group_member);
        } catch (\InvalidArgumentException) {
            // TODO: Generic exception should ideally be replaced with a specific one to catch for this situation
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Invalid role for user.'
            ],
                400
            );
        }

        return new JsonResponse([
            'result' => 'success',
            'message' => 'Group member added.',
            'id' => $group_member->getId()
        ],
            201
        );
    }

    public function updateGroupMember(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';
        $user_id = $args['user_id'] ?? '';
        $new_role = $request->getQueryParams()['role'] ?? '';
        $user = $this->user;

        if (empty($group_id) || empty($user_id) || empty($new_role) || !is_string($new_role)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        // This is a "fix" to prevent an owner from demoting themselves and thus breaking the group.
        if ($user->getId() == $user_id) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You cannot change your own group role.'
            ],
                400
            );
        }

        // TODO: Should *this* be the path to change ownership, or should that be elsewhere?
        if ($new_role == \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'There can only be one owner of a group.'
            ],
                400
            );
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        if ($role != \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to modify members in this group.'
            ],
                403
            );
        }

        /** @var \Restock\Entity\GroupMember $group_member */
        if ($group_member = $this->entityManager->getRepository('\Restock\Entity\GroupMember')->findOneBy(
            ['group' => $group_id, 'user' => $user_id]
        )) {
            try {
                $group_member->setRole($new_role);
            } catch (\InvalidArgumentException) {
                return new JsonResponse([
                    'result' => 'error',
                    'message' => 'Invalid role for group member.'
                ],
                    500
                );
            }

            $this->entityManager->persist($group_member);
            $this->entityManager->flush($group_member);

            return new JsonResponse([
                'result' => 'success',
                'message' => 'Group member updated.'
            ],
                200
            );
        }

        // TODO: Does it matter to return an error saying "user is not a member of this group"? It doesn't do anything regardless.
        return new JsonResponse([
            'result' => 'error',
            'message' => 'Error updating group member.'
        ],
            500
        );
    }

    public function deleteGroupMember(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';
        $user_id = $args['user_id'] ?? '';
        $user = $this->user;

        if (empty($group_id) || empty($user_id)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        if ($role != \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to remove members from this group.'
            ],
                403
            );
        }


        /** @var \Restock\Entity\GroupMember $group_member */
        if ($group_member = $this->entityManager->getRepository('\Restock\Entity\GroupMember')->findOneBy(
            ['group' => $group_id, 'user' => $user_id]
        )) {
            $this->entityManager->remove($group_member);
            $this->entityManager->flush($group_member);

            return new JsonResponse([
                'result' => 'success',
                'message' => 'Member removed from group.'
            ],
                200
            );
        }

        return new JsonResponse([
            'result' => 'error',
            'message' => 'Error removing member from group.'
        ],
            500
        );
    }

    public function getGroupMembers(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';
        $user = $this->user;

        if (empty($group_id)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        /** @var \Restock\Entity\Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);
        /** @var \Restock\Entity\GroupMember[] $members */
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

        return new JsonResponse([
            'result' => 'success',
            'data' => $result
        ],
            200
        );
    }

    /**
     * Create an invitation to a group
     *
     * POST /group/{group_id:number}/invite
     * Accept: application/json
     * X-RestockUserApiToken: {token}
     * X-RestockApiToken: anything
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
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        // TODO: Should other roles be allowed to manage invites?
        if ($role != \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to create invites for this group.'
            ],
                403
            );
        }

        /** @var \Restock\Entity\Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);
        $invite = new \Restock\Entity\Invite($group);

        $this->entityManager->persist($invite);
        $this->entityManager->flush($invite);

        return new JsonResponse([
            'result' => 'success',
            'code' => $invite->getCode()
        ], 201);
    }

    /**
     * Fetch unclaimed invitations
     *
     * GET /group/{group_id:number}/invite
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
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
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        if ($role != \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to list invites for this group.'
            ],
                403
            );
        }

        /** @var \Restock\Entity\Group $group */
        $group = $this->entityManager->getRepository('\Restock\Entity\Group')->findOneBy(['id' => $group_id]);

        return new JsonResponse([
            'result' => 'success',
            // TODO: Should __toString() be implemented in Invite instead?
            'data' => array_map(
                fn(\Restock\Entity\Invite $invite) => $invite->toArray(),
                $group->getInvites()->toArray()
            )
        ], 201);
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
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        $role = $user->getMemberDetails()->findFirst(
            fn($_, \Restock\Entity\GroupMember $group_member) => $group_member->getGroup()->getId() == $group_id
        )?->getRole() ?? '';

        if ($role == '') {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        if ($role != \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to delete invites for this group.'
            ],
                403
            );
        }

        /** @var \Restock\Entity\Invite $invite */
        $invite = $this->entityManager->getRepository('\Restock\Entity\Invite')->findOneBy(['id' => $invite_id]);

        if ($invite === null) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Invite does not exist.'
            ], 400);
        }

        $this->entityManager->remove($invite);
        $this->entityManager->flush($invite);

        return new JsonResponse([
            'result' => 'success',
            'message' => 'Invite deleted.'
        ], 200);
    }

    /**
     * Check if an invitation exists
     *
     * GET /invite/{code:string}
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function getGroupInviteDetails(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $code = $args['code'] ?? '';

        /** @var \Restock\Entity\Invite $invite */
        $invite = $this->entityManager->getRepository('\Restock\Entity\Invite')->findOneBy(['code' => $code]);

        if ($invite === null) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Invite does not exist.'
            ], 400);
        }

        $group = $invite->getGroup();

        return new JsonResponse([
            'result' => 'success',
            'data' => "{$group}"
        ],
            200
        );
    }


    /**
     * Accept an invitation
     *
     * POST /invite/{code:string}
     * Accept: application/json
     * X-RestockApiToken: anything
     * X-RestockUserApiToken: {token}
     *
     * @param ServerRequestInterface $request
     * @param array $args
     * @return ResponseInterface
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function acceptGroupInvite(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $code = $args['code'] ?? '';

        /** @var \Restock\Entity\Invite $invite */
        $invite = $this->entityManager->getRepository('\Restock\Entity\Invite')->findOneBy(['code' => $code]);

        if ($invite === null) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Invite does not exist.'
            ], 400);
        }

        /** @var \Restock\Entity\Group $group */
        $group = $invite->getGroup();
        $group_member = new \Restock\Entity\GroupMember($group, $this->user);

        $this->entityManager->persist($group_member);
        $this->entityManager->remove($invite);
        $this->entityManager->flush();

        return new JsonResponse([
            'result' => 'success',
            'message' => 'Group invite accepted.',
            'id' => $group->getId()
        ],
            201
        );
    }
}
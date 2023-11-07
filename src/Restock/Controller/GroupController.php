<?php

declare(strict_types=1);

namespace Restock\Controller;

use Doctrine\ORM\EntityManager;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GroupController
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getGroupDetails(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function createGroup(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \Restock\Entity\User $owner */
        $owner = $_SESSION['user'];
        $name = $request->getParsedBody()['name'] ?? '';

        // TODO: Duplicate group name error
        $group = new \Restock\Entity\Group($name, $owner);
        $this->entityManager->persist($group);
        $this->entityManager->flush();

        return new JsonResponse([
            'result' => 'success',
            'message' => 'Group has been created.',
            'id' => $group->getId()
        ],
            201
        );
    }

    public function updateGroup(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';
        $name = $request->getQueryParams()['name'] ?? '';

        if (empty($group_id) || empty($name) || !is_string($name)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        /** @var \Restock\Entity\User $user */
        $user = $_SESSION['user'];
        /** @var \Restock\Entity\GroupMember[] $user_groups */
        // There is surely a less strange way to do this
        $user_groups = $user->getMemberDetails();

        $owner = false;
        $member = false;
        foreach ($user_groups as $group) {
            if ($group->getGroup()->getId() == $group_id) {
                $member = true; // Member in the sense that they exist as part of the group
                $owner = $group->getRole() == \Restock\Entity\GroupMember::OWNER;
                break;
            }
        }

        if ($member && !$owner) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to update this group.'
            ],
                403
            );
        }

        if (!$member) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
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
                'message' => 'Group has been updated.'
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

    public function deleteGroup(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $group_id = $args['group_id'] ?? '';

        /** @var \Restock\Entity\User $user */
        $user = $_SESSION['user'];
        /** @var \Restock\Entity\GroupMember[] $user_groups */
        // There is surely a less strange way to do this
        $user_groups = $user->getMemberDetails();

        $owner = false;
        $member = false;
        foreach ($user_groups as $group) {
            if ($group->getGroup()->getId() == $group_id) {
                $member = true; // Member in the sense that they exist as part of the group
                $owner = $group->getRole() == \Restock\Entity\GroupMember::OWNER;
                break;
            }
        }

        if ($member && !$owner) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to delete this group.'
            ],
                403
            );
        }

        if (!$member) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
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
        $role = $request->getParsedBody()['role'] ?? '';
        /** @var \Restock\Entity\User $user */
        $user = $_SESSION['user'];

        if (empty($group_id) || empty($user_id) || !is_string($user_id) || empty($role) || !is_string($role)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        if ($role == \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'There can only be one owner of a group.'
            ],
                400
            );
        }

        /** @var \Restock\Entity\GroupMember[] $user_groups */
        // There is surely a less strange way to do this
        $user_groups = $user->getMemberDetails();

        $owner = false;
        $member = false;
        foreach ($user_groups as $group) {
            if ($group->getGroup()->getId() == $group_id) {
                $member = true; // Member in the sense that they exist as part of the group
                $owner = $group->getRole() == \Restock\Entity\GroupMember::OWNER;
                break;
            }
        }

        if ($member && !$owner) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to add members to this group.'
            ],
                403
            );
        }

        if (!$member) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
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
        $role = $request->getQueryParams()['role'] ?? '';
        /** @var \Restock\Entity\User $user */
        $user = $_SESSION['user'];

        if (empty($group_id) || empty($user_id) || empty($role) || !is_string($role)) {
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
        if ($role == \Restock\Entity\GroupMember::OWNER) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'There can only be one owner of a group.'
            ],
                400
            );
        }

        /** @var \Restock\Entity\GroupMember[] $user_groups */
        // There is surely a less strange way to do this
        $user_groups = $user->getMemberDetails();

        $owner = false;
        $member = false;
        foreach ($user_groups as $group) {
            if ($group->getGroup()->getId() == $group_id) {
                $member = true; // Member in the sense that they exist as part of the group
                $owner = $group->getRole() == \Restock\Entity\GroupMember::OWNER;
                break;
            }
        }

        if ($member && !$owner) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to modify members in this group.'
            ],
                403
            );
        }

        if (!$member) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
            );
        }

        /** @var \Restock\Entity\GroupMember $group_member */
        if ($group_member = $this->entityManager->getRepository('\Restock\Entity\GroupMember')->findOneBy(
            ['group' => $group_id, 'user' => $user_id]
        )) {
            try {
                $group_member->setRole($role);
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
        /** @var \Restock\Entity\User $user */
        $user = $_SESSION['user'];

        if (empty($group_id) || empty($user_id)) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'Required parameter missing.'
            ],
                400
            );
        }

        /** @var \Restock\Entity\GroupMember[] $user_groups */
        // There is surely a less strange way to do this
        $user_groups = $user->getMemberDetails();

        $owner = false;
        $member = false;
        foreach ($user_groups as $group) {
            if ($group->getGroup()->getId() == $group_id) {
                $member = true; // Member in the sense that they exist as part of the group
                $owner = $group->getRole() == \Restock\Entity\GroupMember::OWNER;
                break;
            }
        }

        if ($member && !$owner) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You do not have permission to remove members from this group.'
            ],
                403
            );
        }

        if (!$member) {
            return new JsonResponse([
                'result' => 'error',
                'message' => 'You are not a member of this group, or the group does not exist.'
            ],
                400
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
}
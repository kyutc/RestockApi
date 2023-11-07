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

    public function updateGroup(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
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

    public function addGroupMember(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function updateGroupMember(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }

    public function deleteGroupMember(ServerRequestInterface $request): ResponseInterface
    {
        throw new \Exception("Not implemented.");
    }
}
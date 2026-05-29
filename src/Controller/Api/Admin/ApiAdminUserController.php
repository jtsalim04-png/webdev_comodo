<?php

namespace App\Controller\Api\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users')]
class ApiAdminUserController extends AbstractApiAdminController
{
    private const DEFAULT_RESET_PASSWORD = 'TempPassword123!';

    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $this->denyUnlessAdmin();

        $users = $userRepository->findBy([], ['firstName' => 'ASC']);

        return $this->json(array_map([$this, 'serializeUser'], $users));
    }

    #[Route('', name: 'api_admin_users_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
    ): JsonResponse {
        $this->denyUnlessAdmin();

        $data = $this->decodeJson($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $email = trim((string) ($data['email'] ?? ''));
        $password = $data['password'] ?? null;
        $role = $data['role'] ?? null;

        if ($email === '') {
            return $this->json(['message' => 'email is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_string($password) || $password === '') {
            return $this->json(['message' => 'password is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isAllowedRole(is_string($role) ? $role : null)) {
            return $this->json(['message' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->json(['message' => 'This email is already registered.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName((string) ($data['firstName'] ?? ''));
        $user->setLastName((string) ($data['lastName'] ?? ''));
        $user->setRole($role);
        $user->setIsActive((bool) ($data['isActive'] ?? true));
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json($this->serializeUser($user), Response::HTTP_CREATED);
    }

    #[Route('/{id}/toggle-status', name: 'api_admin_users_toggle_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleStatus(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyUnlessAdmin();

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->getId() === $user->getId()) {
            return $this->json(['message' => 'You cannot disable your own account.'], Response::HTTP_BAD_REQUEST);
        }

        $user->setIsActive(!$user->getIsActive());
        $entityManager->flush();

        return $this->json($this->serializeUser($user));
    }

    #[Route('/{id}/reset-password', name: 'api_admin_users_reset_password', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function resetPassword(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $this->denyUnlessAdmin();

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $user->setPassword($passwordHasher->hashPassword($user, self::DEFAULT_RESET_PASSWORD));
        $entityManager->flush();

        return $this->json(['temporaryPassword' => self::DEFAULT_RESET_PASSWORD]);
    }

    #[Route('/{id}', name: 'api_admin_users_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $this->denyUnlessAdmin();

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        if (array_key_exists('email', $data)) {
            $email = trim((string) $data['email']);
            if ($email === '') {
                return $this->json(['message' => 'email is required'], Response::HTTP_BAD_REQUEST);
            }
            $existing = $userRepository->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['message' => 'This email is already registered.'], Response::HTTP_CONFLICT);
            }
            $user->setEmail($email);
        }

        if (array_key_exists('firstName', $data)) {
            $user->setFirstName((string) $data['firstName']);
        }

        if (array_key_exists('lastName', $data)) {
            $user->setLastName((string) $data['lastName']);
        }

        if (array_key_exists('role', $data)) {
            $role = $data['role'];
            if (!$this->isAllowedRole(is_string($role) ? $role : null)) {
                return $this->json(['message' => 'Invalid role'], Response::HTTP_BAD_REQUEST);
            }
            $user->setRole($role);
        }

        if (array_key_exists('isActive', $data)) {
            $user->setIsActive((bool) $data['isActive']);
        }

        if (!empty($data['password']) && is_string($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $entityManager->flush();

        return $this->json($this->serializeUser($user));
    }

    #[Route('/{id}', name: 'api_admin_users_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyUnlessAdmin();

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->getId() === $user->getId()) {
            return $this->json(['message' => 'You cannot delete your own account.'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function decodeJson(Request $request): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }
}

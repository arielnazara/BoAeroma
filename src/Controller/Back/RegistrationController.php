<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends AbstractController
{

    public function listUtilisateur(UserRepository $userRepo)
    {
        return $this->render('back/registration/liste.html.twig', [
            'utilisateurs' => $userRepo->findAll([], ['id' => 'desc']),
            'user' => true
        ]);
    }

    public function ajoutUtilisateur($id = null, Request $request, UserRepository $userRepo, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        if ($id) {
            $user = $userRepo->find($id);
        } else {
            $user = new User();
        }

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            if ($id) {
                $this->addFlash('userSuccess', 'Modification effectué avec succès');
            } else {
                $this->addFlash('userSuccess', 'Ajout effectué avec succès');
            }

            return $this->redirectToRoute('liste_utilisateur');
        }

        return $this->render('back/registration/ajoutUtilisateur.html.twig', [
            'registrationForm' => $form->createView(),
            'user' => $user,
            'modif' => ($id) ? true : false
        ]);
    }

    public function supprimerUtilisateur(User $user, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('userSuccess', 'Utilisateur supprimée avec succès');
            return $this->redirectToRoute('liste_utilisateur');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }
}

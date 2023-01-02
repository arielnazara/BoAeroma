<?php

namespace App\Controller\Back;

use App\Entity\TypePrestation;
use App\Form\TypePrestationType;
use App\Repository\TypePrestationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("manager/facture/divers/type/prestation")
 */
class TypePrestationController extends AbstractController
{
    /**
     * @Route("/", name="type_prestation_index", methods={"GET"})
     */
    public function index(TypePrestationRepository $typePrestationRepository): Response
    {
        return $this->render('back/type_prestation/index.html.twig', [
            'type_prestations' => $typePrestationRepository->findAll(),
            'invoices' => true,
            'divers' => true
        ]);
    }

    /**
     * @Route("/new", name="type_prestation_new", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $typePrestation = new TypePrestation();
        $form = $this->createForm(TypePrestationType::class, $typePrestation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($typePrestation);
            $entityManager->flush();

            return $this->redirectToRoute('type_prestation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/type_prestation/new.html.twig', [
            'type_prestation' => $typePrestation,
            'form' => $form->createView(),
            'invoices' => true,
            'divers' => true
        ]);
    }

    /**
     * @Route("/{id}", name="type_prestation_show", methods={"GET"})
     */
    public function show(TypePrestation $typePrestation): Response
    {
        return $this->render('back/type_prestation/show.html.twig', [
            'type_prestation' => $typePrestation,
            'invoices' => true,
            'divers' => true
        ]);
    }

    /**
     * @Route("/{id}/edit", name="type_prestation_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, TypePrestation $typePrestation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TypePrestationType::class, $typePrestation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('type_prestation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/type_prestation/edit.html.twig', [
            'type_prestation' => $typePrestation,
            'form' => $form->createView(),
            'invoices' => true,
            'divers' => true
        ]);
    }

    /**
     * @Route("/{id}", name="type_prestation_delete", methods={"POST"})
     */
    public function delete(Request $request, TypePrestation $typePrestation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$typePrestation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($typePrestation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('type_prestation_index', [], Response::HTTP_SEE_OTHER);
    }
}

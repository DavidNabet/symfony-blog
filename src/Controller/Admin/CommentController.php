<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CommentController
 * @package App\Controller
 * @Route("/commentaire")
 */
class CommentController extends AbstractController
{
    /**
     * @Route("/{id}")
     */
    public function index(Article $article)
    {

        $repository = $this->getDoctrine()->getRepository(Comment::class);

        $comment = $repository->findBy(['article' => $article], ['datePublication' => 'DESC']);

        return $this->render('admin/comment/index.html.twig', [
            'nbComm' => $comment
        ]);
    }
}

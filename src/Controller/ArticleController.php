<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ArticleController
 * @package App\Controller
 * @Route("/article")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/{id}", requirements={"id":"\d+"})
     */
    public function index(Article $article, Request $request, $id)
    {
        /*
        v * afficher toutes les informations de l'article
        v * avec l'image s'il y en a une
         *
        v    * Sous l'article, si l'utilisateur n'est pas connecté,
        v    * l'inviter à le faire pour pouvoir écrire un commentaire
        v * Sinon, lui afficher un formulaire avec un textarea
        v * pour pouvoir écrire un commentaire.
         *
        v    * Nécessite une entité Commentaire :
        v    * - content (text en bdd)
        v    * - une date de publication (datetime)
        v    * - user (l'utilisateur qui écrit un commentaire) ManyToOne
        v    * - article (l'article sur lequel on écrit le commentaire)
         *
        v   * Nécessite le form type qui va avec, contenant le textarea,
        v   * le contenu du commentaire ne doit pas être vide.
         *
        v * Lister les commentaires en dessous, avec nom utilisateur,
        v * date de publication, contenu du message
         */

        /* PEUT ETRE PAS BESOIN
        $repository = $this->getDoctrine()->getRepository(Article::class);
        $article = $repository->findBy(['id' => $article]);
        */

        //$user = $this->getUser();
        //$date = new \DateTime();

        $em = $this->getDoctrine()->getManager();

        $comment = new Comment();

        //$comment->setUser($user);
        //$comment->setDatePublication($date);
        //$comment->setArticle($article); OU AUTRE MANIERE DE FAIRE DANS LE ISVALID()

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if($form->isSubmitted()) {
            if ($form->isValid()) {

                // en premier avant de flusher
                $comment
                        ->setUser($this->getUser())
                        ->setDatePublication(new \DateTime())
                        ->setArticle($article)
                ;

                $em->persist($comment);
                $em->flush();

                $this->addFlash('success', 'Votre commentaire a bien été enregistré');

                // redirection vers la page sur laquelle
                // on est pour ne pas être en POST
                return $this->redirectToRoute(
                    // la route de la page courante
                    //$request->get('_route'),
                    'app_article_index',
                    // ['id' => $article->getId() ]
                    ['id' => $id]
                );

            } else {
                $this->addFlash('error', 'Saisissez dans le champ du commentaire !');
            }
        }

        $repository = $em->getRepository(Comment::class);
        $commentAffiche = $repository->findBy(['article' => $article], ['datePublication' => 'DESC'], 10);


        return $this->render('article/index.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
            'comments' => $commentAffiche
        ]);

    }

}

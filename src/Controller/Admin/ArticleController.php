<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Form\ArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ArticleController
 * @package App\Controller\Admin
 * @Route("/article")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index()
    {
        /*
         * faire la page qui liste les articles dans un tableau html
         * avec un nom de la catégorie
         * nom de l'auteur
         * et date au format français
         * (tous les champs sauf le contenu)
         */

        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository(Article::class);

        //$articles = $repository->findBy([], ['id' => 'asc']);
        $articles = $repository->findBy([], ['publicationDate' => 'desc']);

        return $this->render('admin/article/index.html.twig',
            [
                'articles' => $articles
            ]);
    }

    /**
     * ajouter la méthode edit() qui fait le rendu du formulaire et son traitement
     * mettre un lien AJOUTER dans la page de liste
     *
     * Validation : tous les champs obligatoires
     *
     * En création :
     * - setter l'auteur avec l'utilisateur connecté
     *  ($this->getUser() depuis le controleur)
     * - mettre la date de publication à maintenant
     *
     * Adapter la route et le contenu de la méthode
     * pour que la page fonctionne en modifications
     * et ajouter le bouton MODIFIER dans page de liste
     *
     * Enregistrer l'article en bdd si le formulaire est bien rempli
     * puis rediriger vers la liste avec un message de confirmation
     */

    /**
     * @Route("/edition/{id}", defaults={"id": null}, requirements={"id": "\d+"})
     */
    public function edit(Request $request, $id)
    {
        $user = $this->getUser();

        // passe dans le sette de la classe Article
        //$article->setPublicationDate(new \DateTime());
        // OU dans le constructeur de la classe Article

        $date = new \DateTime();

        $em = $this->getDoctrine()->getManager();
        $originalImage = null;

        if(is_null($id)){ // création de l'article
            $article = new Article();

            // auteur setter
            $article->setAuthor($user);

            // on sette la date de publication à la date du moment de l'enregistrement
            $article->setPublicationDate($date);
            $message = 'L\'article à été enregistrée';

        } else { // modification
            $article = $em->find(Article::class, $id);

            $message = 'L\'article à été modifié';

            if(!is_null($article->getImage())){
                // nom du fichier venant de la bdd
                $originalImage = $article->getImage();
                // on sette l'image avec un objet File
                // pour le traitement par le formulaire
                $article->setImage(
                    new File($this->getParameter('upload_dir') . $originalImage)
                );
            }

            // 404 si l'id reçu dans l'url n'est pas en bdd
            if (is_null($article)) {
                throw new NotFoundHttpException();
            }

        }

        // création du formulaire lié à la catégorie
        $formArt = $this->createForm(ArticleType::class, $article);

        // le formulaire analyse la requête HTTP
        // et traite le formulaire s'il a été soumis
        $formArt->handleRequest($request);

        // si le formulaire a été envoyé
        if( $formArt->isSubmitted() ) {

            if( $formArt->isValid() ){
                /**
                 * @var UploadedFile $image
                 */
                $image = $article->getImage();

                // s'il y a eu une image uploadée
                if (!is_null($image)){
                    // nom de l'image dans notre application
                    // uniqid() = référence unique d'une image composé de chaines de caractères
                    $filename = uniqid() . '.' . $image->guessExtension();

                    // équivalent de move_uploaded_file() : déplace, renomme et supprime du répertoire
                    $image->move(
                        // répertoire de destination
                        // cf le paramètre upload_dir dans config/services.yaml
                        $this->getParameter('upload_dir'),
                        // nom du fichier
                        $filename
                    );

                    // on sette l'attribut image de l'attribut avec le nom
                    // de l'image pour enregistrement en bdd
                    $article->setImage($filename);

                    // en modification, on supprime l'ancienne image s'il y en a une
                    if (!is_null($originalImage)){
                       unlink($this->getParameter('upload_dir') . $originalImage);
                    }
                } else {
                    // sans upload, pour la modification, on sette l'attribut
                    // image avec le nom de l'ancienne image
                    $article->setImage($originalImage);
                }

                $em->persist($article);
                $em->flush();

                // message de confirmation
                $this->addFlash('success', $message);
                // redirection vers la liste
                return $this->redirectToRoute('app_admin_article_index');
            } else {
                $this->addFlash('error', 'Le formulaire contient des erreurs');

            }
        }

        return $this->render('admin/article/edit.html.twig',
            [
                'form' => $formArt->createView(),
                'original_image' => $originalImage
            ]);
    }

    /**
     * Categorie existante donc on met un id
     * @Route("/suppression/{id}")
     */
    public function delete(Article $article)
    {
        $em = $this->getDoctrine()->getManager();

        $em->remove($article);
        $em->flush();

        $this->addFlash(
            'success',
            'L\'article à été supprimé'
        );

        return $this->redirectToRoute('app_admin_article_index');
    }
}
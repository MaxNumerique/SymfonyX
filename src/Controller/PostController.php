<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Tag;
use App\Form\PostType;
use App\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function PHPUnit\Framework\matches;

class PostController extends AbstractController
{
    private string $uploadsDir;
    public function __construct(string $uploadsDir)
    {
        $this->uploadsDir = $uploadsDir;
    }

    #[Route('/', name: 'app_post')]
    public function index(PostRepository $postRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $categorieColors = [
            'Politique' => 'bg-red-500',
            'Cinéma' => 'bg-green-500',
            'Sport' => 'bg-yellow-500',
            'Divers' => 'bg-blue-500',
            'Web' => 'bg-purple-500',
            'Food' => 'bg-pink-500',
            'Sciences' => 'bg-orange-500',
            'Voyage' => 'bg-gray-500',
            'Animaux' => 'bg-black',
        ];

        // $posts = $postRepository->findAll();
        // $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);
        $queryBuilder = $postRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC');

        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /* numéro de page */
            20 /* nombre de posts par page */
        );

        return $this->render('post/index.html.twig', [
            // 'posts' => $posts,
            'pagination' => $pagination,
            'categorieColors' => $categorieColors,
        ]);
    }

    #[Route('/post/new', name: 'app_post_new', priority: 1)]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $post = new Post();
        $post->setCreatedAt(new \DateTimeImmutable());
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->uploadsDir,
                        $newFilename
                    );
                    $post->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('warning', 'Une erreur est survenue lors de l\'importation de l\'image');
                }
            } else {
                $post->setImage('http://dummyimage.com/229x100.png/cc0000/ffffff');
            }

            // Gestion des tags
            $hashtagsString = $request->request->get('hashtags');
            if ($hashtagsString) {
                // Utiliser une expression régulière pour trouver tous les hashtags dans la chaîne
                preg_match_all('~#(\w+)~', $hashtagsString, $matches);

                // $matches[1] contient tous les hashtags sans le symbole "#"
                $hashtags = $matches[1];

                foreach ($hashtags as $hashtag) {
                    // Chercher si le tag existe déjà
                    $tag = $em->getRepository(Tag::class)->findOneBy(['name' => $hashtag]);

                    // Si le tag n'existe pas, le créer
                    if (!$tag) {
                        $tag = new Tag();
                        $tag->setName($hashtag); // Définir le nom du tag sans le symbole "#"
                        $em->persist($tag);
                    }

                    // Associer le tag au post
                    $post->addTag($tag);
                }
            }

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Votre article' . ' ' . $post->getTitle() . ' ' . 'a bien été créé !');

            // $this->addFlash('info', 'Votre article' . ' ' . $post->getTitle() . ' ' . 'a bien été mis à jour !');

            return $this->redirectToRoute('app_post');
        }
        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/show/{id}', name: 'app_post_show')]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    // #[Route('/post/category/{id}', name: 'app_post_category')]
    // public function category(PostRepository $postRepository, $id): Response
    // {
    //     $posts = $postRepository->findByCategory($id);
    //     return $this->render('post/category.html.twig', [
    //         'posts' => $posts,
    //     ]);
    // }

    #[Route('/post/category/{id}', name: 'app_post_category')]
    public function category(PostRepository $postRepository, $id, Connection $connection): Response
    {
        $sql = 'SELECT * FROM post WHERE category_id = :id';
        $posts = $connection->executeQuery($sql, ['id' => $id])->fetchAllAssociative();
        $posts = $postRepository->findByCategory($id);
        return $this->render('post/category.html.twig', [
            'posts' => $posts,
        ]);
    }
}

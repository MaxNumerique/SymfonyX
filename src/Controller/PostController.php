<?php

namespace App\Controller;

use App\Entity\Category;
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

        $query = $postRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6 // Nombre d'éléments par page
        );

        return $this->render('post/index.html.twig', [
            'posts' => $pagination,
            'categorieColors' => $categorieColors,
        ]);
    }

    #[Route('/post/new', name: 'app_post_new', priority: 1)]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $post = new Post();
        $post->setCreatedAt(new \DateTimeImmutable());

        $user = $this->getUser();  // Récupère l'utilisateur connecté
        $post->setUser($user);     // Associe l'utilisateur au post
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
            $hashtagsString = $request->request->get('hashtags', ''); // Fournir une chaîne vide par défaut
            if (!empty($hashtagsString)) {
                preg_match_all('~#(\w+)~', $hashtagsString, $matches);
                $hashtags = $matches[1] ?? []; // Utiliser un tableau vide si aucun hashtag n'est trouvé

                foreach ($hashtags as $hashtag) {
                    $tag = $em->getRepository(Tag::class)->findOneBy(['name' => $hashtag]);
                    if (!$tag) {
                        $tag = new Tag();
                        $tag->setName($hashtag);
                        $em->persist($tag);
                    }
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

    #[Route('/category/{id}', name: 'app_post_category')]
    public function postsByCategory(Category $category, PostRepository $postRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $postRepository->createQueryBuilder('p')
            ->where('p.category = :category')
            ->setParameter('category', $category)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('post/index.html.twig', [
            'posts' => $pagination,
            'categorieColors' => [
                'Politique' => 'bg-red-500',
                'Cinéma' => 'bg-green-500',
                'Sport' => 'bg-yellow-500',
                'Divers' => 'bg-blue-500',
                'Web' => 'bg-purple-500',
                'Food' => 'bg-pink-500',
                'Sciences' => 'bg-orange-500',
                'Voyage' => 'bg-gray-500',
                'Animaux' => 'bg-black',
            ],
            'selectedCategory' => $category
        ]);
    }
}

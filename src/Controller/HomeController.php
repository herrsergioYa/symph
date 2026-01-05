<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(EntityManagerInterface $em, ProductRepository $productRepository): Response
    {
        /*$product = new Product();
        $product->setName('MacBook Pro');
        $product->setPrice(2990);
        $product->setCreatedAt(new \DateTime());

        $em->persist($product);
        $em->flush();*/

        $q = $em->createQueryBuilder()
            ->select('q', 'p')
            ->from(Product::class, 'p')
            ->from(Product::class, 'q');
        $dql = $q->getDQL();
        $res = $q
            ->getQuery()->getResult();
        /** @var Product[] $res */
        foreach ($res as $product) {
            if($product->getId() == 1) {
                $product->setColor('red');
                $categoryQuery = $em->createQueryBuilder()
                    ->select('p', 'c')
                    ->from(Category::class, 'c')
                    ->join('c.products', 'p')
                    ->where('c.id = :category')
                    ->orWhere('c.id = :category')
                    ->andWhere('c.id = :category')
                    ->setParameter('category', 1)
                    ->getQuery();
                $dql = $categoryQuery->getDQL();
                $category = $categoryQuery
                    ->getOneOrNullResult();
                $product->setCategory($category);
                $category->getProducts()->add($product);
            } else {
                $product->setColor('silver');
            }
        }
        $em->flush();

        /*$c = new Category();
        $c->setName('Test category');
        $em->persist($c);
        $em->flush();*/
        ///require __DIR__ . '/../../local/php_interface/init.php';

        gsv_dump(0);
        $b24app = new \Gsv\Util\Bitrix24\Bitrix24App();

        $dql = "SELECT p, c
        FROM App\Entity\Product p, App\Entity\Product c
        WHERE p.price > :minPrice";

        $query = $em->createQuery($dql)
            ->setParameter('minPrice', 100);

        $results = $query->getScalarResult();

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }


    #[Route('/kaira/kaira', name: 'kaira')]
    public function kaira(EntityManagerInterface $em, \App\Service\UserManager $u): Response
    {
        $q = $em->createQueryBuilder()
            ->select('c')
            ->from(Category::class, 'c');
        $dql = $q->getDQL();
        $categories = $q
            ->getQuery()
            ->getResult();
        /** @var Category[] $categories */
        foreach ($categories as $category) {
        }
        $em->flush();

        $user = $u->getCurrentUser();

        $q = $em->createQueryBuilder()
            ->select('c', 'p')
            ->from(Category::class, 'c')
            ->leftJoin('c.products', 'p')
            ->where('c.code = :code')
            ->setParameter('code', 'laptop')
            /*->setMaxResults(1)*/;
        $dql = $q->getDQL();
        $laptop = $q->getQuery()->getOneOrNullResult();

        return $this->render('kaira/index.html.twig', [
            'categories' => $categories,
            'laptop' => $laptop,
        ]);
    }

    #[Route('/kaira/auth/{userId}', name: 'auth')]
    public function auth(\App\Service\UserManager $u, string $userId): Response
    {
        $u->authorize($userId);

        return $this->redirectToRoute('kaira');
    }

    #[Route('/kaira/{code}', name: 'section')]
    public function section(string $code, EntityManagerInterface $em): Response
    {
        $q = $em->createQueryBuilder()
            ->select('c', 'p')
            ->from(Category::class, 'c')
            ->leftJoin('c.products', 'p')
            ->where('c.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1);
        $dql = $q->getDQL();
        $category = $q->getQuery()
            ->getOneOrNullResult();
        if($category == null) {
            throw $this->createNotFoundException("Категория не существует");
        } else {
            return $this->render('kaira/section.html.twig', [
                'category' => $category,
            ]);
        }
    }

    #[Route('/kaira/p/{code}', name: 'product')]
    public function product(string $code, EntityManagerInterface $em): Response
    {
        $q = $em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1);
        $dql = $q->getDQL();
        $product = $q->getQuery()
            ->getOneOrNullResult();
        if($product == null) {
            throw $this->createNotFoundException("Категория не существует");
        } else {
            return $this->render('kaira/product.html.twig', [
                'product' => $product,
            ]);
        }
    }
}

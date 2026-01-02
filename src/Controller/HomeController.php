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
    public function kaira(EntityManagerInterface $em): Response
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

        return $this->render('kaira/index.html.twig', [
            'categories' => $categories,
        ]);
    }
}

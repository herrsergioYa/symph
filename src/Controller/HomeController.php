<?php

namespace App\Controller;

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
            $product->setColor('silver');
        }
        $em->flush();
        
        require __DIR__ . '/../../local/php_interface/init.php';

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
}

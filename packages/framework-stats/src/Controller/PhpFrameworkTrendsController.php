<?php

declare(strict_types=1);

namespace TomasVotruba\FrameworkStats\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symplify\PackageBuilder\Parameter\ParameterProvider;
use TomasVotruba\Blog\Repository\PostRepository;
use TomasVotruba\Website\ValueObject\Option;
use TomasVotruba\Website\ValueObject\RouteName;

final class PhpFrameworkTrendsController extends AbstractController
{
    /**
     * @var mixed[]
     */
    private array $phpFrameworkTrends = [];

    public function __construct(private PostRepository $postRepository, ParameterProvider $parameterProvider)
    {
        $this->phpFrameworkTrends = $parameterProvider->provideArrayParameter(Option::PHP_FRAMEWORK_TRENDS);
    }

    #[Route(path: 'php-framework-trends', name: RouteName::PHP_FRAMEWORK_TRENDS)]
    public function __invoke(): Response
    {
        $promoPost = $this->postRepository->get(202);
        return $this->render('php-framework-trends.twig', [
            'title' => 'PHP Framework Trends',
            'promo_post' => $promoPost,
            'php_framework_trends' => $this->phpFrameworkTrends,
        ]);
    }
}

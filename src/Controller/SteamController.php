<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Wa72\HtmlPageDom\HtmlPage;
use Wa72\HtmlPageDom\HtmlPageCrawler;

#[Route('/steam', name: 'app_steam')]
class SteamController extends AbstractController
{
    public function __invoke(): Response
    {
        do {
            $steam = file_get_contents('http://store.steampowered.com/explore/random/');
            $dom = new HtmlPage($steam);

            $title = $dom->getTitle();
            $img = $dom->filter('.game_header_image_full');

        } while ($img->count() <= 0);

        $split = explode(' ', $title);
        if ($split[0] === 'Save' && $split[2] === 'on') {
            $title = implode(' ', array_splice($split, 3));
        }

        return $this->render('steam/steam.html.twig', [
            'title' => $title,
            'imgurl' => $img->getAttribute('src'),
            'key' => $this->generateKey(),
        ]);
    }

    private function generateRandomString(int $length = 10): string {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function generateKey(): string {
        if (rand(0,2) === 2) {
            return $this->generateRandomString(15);
        } else {
            $groups = rand(0,1) === 1 ? 3 : 5;
            return implode('-', array_map(function() { return $this->generateRandomString(5); }, array_fill(0, $groups, '')));
        }
    }
}
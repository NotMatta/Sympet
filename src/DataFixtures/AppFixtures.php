<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $categoryNames = [
            'Dogs',
            'Cats',
            'Birds',
            'Fish',
            'Small Animals',
            'Reptiles',
            'Accessories',
        ];

        $categories = [];
        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $categories[] = $category;
        }

        $productNames = [
            'Premium Dry Dog Food',
            'Grain-Free Puppy Kibble',
            'Catnip Crunch Treats',
            'Indoor Cat Litter',
            'Parrot Seed Blend',
            'Canary Vitamin Mix',
            'Tropical Fish Flakes',
            'Betta Water Conditioner',
            'Hamster Wheel Deluxe',
            'Rabbit Hay Bundle',
            'Leash with Reflective Stitch',
            'Leather Dog Collar',
            'Automatic Pet Water Fountain',
            'Raised Feeding Bowl Set',
            'Soft Cat Bed',
            'Interactive Feather Wand',
            'Bird Cage Perch Pack',
            'Aquarium Air Pump',
            'Aquarium Decoration Kit',
            'Small Pet Carrier',
            'Reptile Heat Lamp',
            'Terrarium Substrate',
            'Pet Grooming Brush',
            'Nail Clipper Set',
            'Dental Chew Sticks',
            'Puppy Training Pads',
            'Organic Cat Wet Food',
            'High-Protein Dog Snacks',
        ];

        foreach ($productNames as $name) {
            $product = new Product();
            $product
                ->setName($name)
                ->setDescription($faker->paragraph(2))
                ->setPrice($faker->randomFloat(2, 4, 160))
                ->setQuantity($faker->numberBetween(5, 200))
                ->setVisibility(true)
                ->setImage(sprintf('https://picsum.photos/seed/%s/800/800', rawurlencode($name)))
                ->setCategory($categories[array_rand($categories)])
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-120 days', 'now')));

            $manager->persist($product);
        }

        $manager->flush();
    }
}

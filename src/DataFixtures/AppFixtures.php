<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Produit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // ==================== CATÉGORIES ====================
        $categorie1 = new Categorie();
        $categorie1->setNom('Nourriture');
        $categorie1->setDescription('Alimentation pour chiens et chats');
        $manager->persist($categorie1);

        $categorie2 = new Categorie();
        $categorie2->setNom('Accessoires');
        $categorie2->setDescription('Colliers, laisses, gamelles et plus');
        $manager->persist($categorie2);

        $categorie3 = new Categorie();
        $categorie3->setNom('Jouets');
        $categorie3->setDescription('Jouets pour occuper vos animaux');
        $manager->persist($categorie3);

        $categorie4 = new Categorie();
        $categorie4->setNom('Hygiène');
        $categorie4->setDescription('Shampoings, brosses et soins');
        $manager->persist($categorie4);

        $manager->flush();

        // ==================== PRODUITS ====================
        
        // Nourriture
        $produits = [
            ['nom' => 'Croquettes Royal Canin Chiot', 'description' => 'Croquettes adaptées aux chiots de toutes races. Riche en protéines.', 'prix' => 45.90, 'stock' => 50, 'categorie' => $categorie1, 'image' => 'https://placehold.co/400x300/FF8C00/FFFFFF?text=Croquettes+Chiot'],
            ['nom' => 'Croquettes Friskies Chat Adulte', 'description' => 'Croquettes au poulet pour chat adulte stérilisé.', 'prix' => 22.50, 'stock' => 80, 'categorie' => $categorie1, 'image' => 'https://placehold.co/400x300/FF8C00/FFFFFF?text=Friskies+Chat'],
            ['nom' => 'Pâtée Gourmet Gold', 'description' => 'Pâtée pour chat à la viande sélectionnée. Lot de 12.', 'prix' => 18.00, 'stock' => 40, 'categorie' => $categorie1, 'image' => 'https://placehold.co/400x300/FF8C00/FFFFFF?text=Patee+Chat'],
            ['nom' => 'Os à mâcher Pedigree', 'description' => 'Os comestible pour chien, bon pour les dents.', 'prix' => 8.90, 'stock' => 100, 'categorie' => $categorie1, 'image' => 'https://placehold.co/400x300/FF8C00/FFFFFF?text=Os+Chien'],

            // Accessoires
            ['nom' => 'Collier en cuir pour chien', 'description' => 'Collier réglable en cuir véritable. Taille M.', 'prix' => 25.00, 'stock' => 30, 'categorie' => $categorie2, 'image' => 'https://placehold.co/400x300/4682B4/FFFFFF?text=Collier+Cuir'],
            ['nom' => 'Laisse rétractable', 'description' => 'Laisse automatique 5 mètres pour chien jusqu\'à 20 kg.', 'prix' => 35.00, 'stock' => 25, 'categorie' => $categorie2, 'image' => 'https://placehold.co/400x300/4682B4/FFFFFF?text=Laisse'],
            ['nom' => 'Gamelle inox double', 'description' => 'Gamelle double en inox pour eau et nourriture.', 'prix' => 19.90, 'stock' => 45, 'categorie' => $categorie2, 'image' => 'https://placehold.co/400x300/4682B4/FFFFFF?text=Gamelle'],
            ['nom' => 'Panier pour chat', 'description' => 'Panier douillet en fausse fourrure. Lavable.', 'prix' => 29.90, 'stock' => 20, 'categorie' => $categorie2, 'image' => 'https://placehold.co/400x300/4682B4/FFFFFF?text=Panier+Chat'],

            // Jouets
            ['nom' => 'Balle rebondissante', 'description' => 'Balle en caoutchouc résistante pour chien.', 'prix' => 6.50, 'stock' => 70, 'categorie' => $categorie3, 'image' => 'https://placehold.co/400x300/32CD32/FFFFFF?text=Balle'],
            ['nom' => 'Canne à pêche pour chat', 'description' => 'Canne avec plume et grelot pour faire jouer votre chat.', 'prix' => 12.00, 'stock' => 55, 'categorie' => $categorie3, 'image' => 'https://placehold.co/400x300/32CD32/FFFFFF?text=Canne+Chat'],
            ['nom' => 'Corde à tirer', 'description' => 'Corde nouée pour chien. Jeu de traction.', 'prix' => 9.90, 'stock' => 60, 'categorie' => $categorie3, 'image' => 'https://placehold.co/400x300/32CD32/FFFFFF?text=Corde'],
            ['nom' => 'Souris mécanique', 'description' => 'Souris à piles qui se déplace toute seule.', 'prix' => 15.00, 'stock' => 35, 'categorie' => $categorie3, 'image' => 'https://placehold.co/400x300/32CD32/FFFFFF?text=Souris'],

            // Hygiène
            ['nom' => 'Shampoing chien pelage brillant', 'description' => 'Shampoing doux au pH neutre pour chien.', 'prix' => 14.90, 'stock' => 40, 'categorie' => $categorie4, 'image' => 'https://placehold.co/400x300/9370DB/FFFFFF?text=Shampoing'],
            ['nom' => 'Brosse démêlante', 'description' => 'Brosse pour poils longs et mi-longs.', 'prix' => 11.00, 'stock' => 35, 'categorie' => $categorie4, 'image' => 'https://placehold.co/400x300/9370DB/FFFFFF?text=Brosse'],
            ['nom' => 'Coupe-griffes', 'description' => 'Coupe-griffes de précision pour chiens et chats.', 'prix' => 18.50, 'stock' => 25, 'categorie' => $categorie4, 'image' => 'https://placehold.co/400x300/9370DB/FFFFFF?text=Coupe+Griffes'],
        ];

        foreach ($produits as $data) {
            $produit = new Produit();
            $produit->setNom($data['nom']);
            $produit->setDescription($data['description']);
            $produit->setPrix($data['prix']);
            $produit->setStock($data['stock']);
            $produit->setImage($data['image']);
            $produit->setCategorie($data['categorie']);
            $manager->persist($produit);
        }

        $manager->flush();
    }
}
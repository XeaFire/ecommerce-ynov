<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => ['placeholder' => 'Nom de votre produit']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 5, 'placeholder' => 'Décrivez votre produit']
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'attr' => ['placeholder' => '0.00']
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité disponible',
                'attr' => ['min' => 1]
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Callback([
                        'callback' => function ($file, ExecutionContextInterface $context) {
                            if (!$file) {
                                return;
                            }
                            
                            // Vérifier la taille du fichier (2Mo max)
                            $maxSize = 2 * 1024 * 1024; // 2Mo en octets
                            if ($file->getSize() > $maxSize) {
                                $context->buildViolation('Le fichier est trop volumineux ({{ size }} Mo). La taille maximale autorisée est {{ limit }} Mo.')
                                    ->setParameters([
                                        '{{ size }}' => round($file->getSize() / 1048576, 2),
                                        '{{ limit }}' => 2
                                    ])
                                    ->addViolation();
                                return;
                            }
                            
                            // Vérifier l'extension du fichier
                            $originalFilename = $file->getClientOriginalName();
                            $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
                            $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            
                            if (!in_array($extension, $validExtensions)) {
                                $context->buildViolation('Veuillez uploader une image valide (JPG, PNG, GIF ou WEBP).')
                                    ->addViolation();
                            }
                        }
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
} 
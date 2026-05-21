<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'Enter email'],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'required' => false,
                'mapped' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Enter password (leave blank to keep current when editing)',
                    'value' => '********',
                    'autocomplete' => 'new-password',
                    'data-mask-placeholder' => '********'
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'attr' => ['placeholder' => 'Enter first name'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['placeholder' => 'Enter last name'],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'User Role',
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Organizer' => 'ROLE_ORGANIZER',
                    'User' => 'ROLE_USER',
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active Account',
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();
            $options = $form->getConfig()->getOptions();
            
            // Get the raw password from the form field
            $rawPassword = $form->get('password')->getData();
            
            // Only hash the password if it's provided and not already hashed
            if ($rawPassword && !empty($rawPassword) && !preg_match('/^\$2[ayb]\$.{56}$/', $rawPassword)) {
                if (isset($options['password_hasher']) && $options['password_hasher'] instanceof UserPasswordHasherInterface) {
                    $hashedPassword = $options['password_hasher']->hashPassword($user, $rawPassword);
                    $user->setPassword($hashedPassword);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'password_hasher' => null,
        ]);
    }
}


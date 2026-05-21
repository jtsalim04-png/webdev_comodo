<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => function(Event $event) {
                    return $event->getTitle() . ' - ' . $event->getEventDate()->format('M d, Y');
                },
                'choice_attr' => function(Event $event) {
                    return ['data-price' => $event->getPrice()];
                },
                'placeholder' => 'Select an event',
                'label' => 'Event',
            ])
            ->add('customer', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFirstName() . ' ' . $user->getLastName();
                },
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->setParameter('role', 'ROLE_USER')
                        ->orderBy('u.firstName', 'ASC');
                },
                'placeholder' => 'Select a user',
                'label' => 'Customer',
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'PHP',
                'disabled' => $options['lock_price'],
                'required' => true,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed',
                    'Cancelled' => 'cancelled',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'lock_price' => false, // true = price read-only (e.g., at purchase time)
        ]);
    }
}


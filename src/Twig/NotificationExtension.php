<?php

namespace App\Twig;

use App\Service\PurchaseNotificationProvider;
use Twig\Attribute\AsTwigFunction;

final class NotificationExtension
{
    public function __construct(
        private PurchaseNotificationProvider $purchaseNotificationProvider,
    ) {
    }

    #[AsTwigFunction('purchase_notifications')]
    public function getPurchaseNotifications(int $limit = 20): array
    {
        return $this->purchaseNotificationProvider->getNotifications($limit);
    }
}

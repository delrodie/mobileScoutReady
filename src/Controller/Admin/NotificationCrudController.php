<?php

namespace App\Controller\Admin;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Services\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class NotificationCrudController extends AbstractCrudController
{
    public function __construct(
        private NotificationService $notificationService,
        private AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Notification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Notifications')
            ->setEntityLabelInSingular('Notification')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Gestion des Notifications')
            ->setPageTitle('new', 'Créer une notification')
            ->setPageTitle('edit', 'Modifier une notification')
            ->setPageTitle('detail', 'Détails de la notification')
            ->setPaginatorPageSize(20)
            ->setHelp('new', 'Créez une notification qui sera envoyée aux scouts. Vous pourrez choisir de l\'envoyer à tous ou à des utilisateurs spécifiques.')
            ->setHelp('edit', 'Modifiez les détails de la notification. Les changements n\'affecteront pas les notifications déjà envoyées.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('titre', 'Titre')
            ->setHelp('Le titre de la notification (court et accrocheur)');

        yield TextareaField::new('message', 'Message')
            ->setHelp('Le contenu détaillé de la notification')
            ->hideOnIndex();

        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Information' => Notification::TYPE_INFO,
                'Succès' => Notification::TYPE_SUCCESS,
                'Avertissement' => Notification::TYPE_WARNING,
                'Danger' => Notification::TYPE_DANGER,
            ])
            ->renderAsBadges([
                Notification::TYPE_INFO => 'info',
                Notification::TYPE_SUCCESS => 'success',
                Notification::TYPE_WARNING => 'warning',
                Notification::TYPE_DANGER => 'danger',
            ]);

        yield ChoiceField::new('typeCible', 'Ciblage')
            ->setChoices([
                'Tous les scouts' => Notification::TARGET_ALL,
                'Scouts spécifiques' => Notification::TARGET_SPECIFIC,
            ])
            ->setHelp('Définit qui recevra cette notification')
            ->renderAsBadges([
                Notification::TARGET_ALL => 'primary',
                Notification::TARGET_SPECIFIC => 'secondary',
            ]);

        yield ChoiceField::new('cible', 'Cible spécifique')
            ->setChoices(Notification::getCiblesDisponibles())
            ->setHelp('Sélectionnez le groupe ciblé')
            ->hideOnIndex()
            ->setFormTypeOption('required', false);

        yield TextField::new('urlAction', 'URL de redirection')
            ->setHelp('URL vers laquelle rediriger lors du clic (optionnel)')
            ->hideOnIndex();

        yield TextField::new('libelleAction', 'Libellé du bouton')
            ->setHelp('Texte du bouton d\'action (ex: "Voir l\'activité")')
            ->hideOnIndex();

        yield TextField::new('icone', 'Classe CSS icône')
            ->setHelp('Classe Bootstrap Icons (ex: bi-calendar-event)')
            ->hideOnIndex();

        yield BooleanField::new('estActif', 'Active')
            ->setHelp('Seules les notifications actives sont visibles');

        yield DateTimeField::new('expireLe', 'Date d\'expiration')
            ->setHelp('La notification ne sera plus visible après cette date')
            ->hideOnIndex();

        // Affichage conditionnel selon la page
        if ($pageName === Crud::PAGE_DETAIL) {
            yield DateTimeField::new('', 'Créée le')
                ->setFormat('dd/MM/yyyy HH:mm');

            yield AssociationField::new('utilisateurNotifications', 'Destinataires')
                ->setTemplatePath('admin/field/utilisateur_notifications.html.twig');
        }

        // Sur l'index, afficher un résumé
        if ($pageName === Crud::PAGE_INDEX) {
            yield TextField::new('statistiques', 'Envoyée à')
                ->formatValue(function ($value, Notification $notification) {
                    $count = $notification->getUtilisateurNotifications()->count();
                    return $count > 0 ? $count . ' scout(s)' : 'Non envoyée';
                });
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action personnalisée : Envoyer à tous
        $envoyerATous = Action::new('envoyerATous', 'Envoyer à tous les scouts')
            ->linkToCrudAction('envoyerATous')
            ->displayIf(fn (Notification $notification) =>
                $notification->getTypeCible() === Notification::TARGET_ALL &&
                $notification->getUtilisateurNotifications()->isEmpty()
            )
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-paper-plane');

        // Action personnalisée : Envoyer à des scouts spécifiques
        $envoyerACible = Action::new('envoyerACible', 'Envoyer au groupe ciblé')
            ->linkToCrudAction('envoyerACible')
            ->displayIf(fn (Notification $notification) =>
                $notification->getTypeCible() === Notification::TARGET_SPECIFIC &&
                $notification->getCible() !== null &&
                $notification->getUtilisateurNotifications()->isEmpty()
            )
            ->setCssClass('btn btn-primary')
            ->setIcon('fa fa-users');

        return $actions
            ->add(Crud::PAGE_DETAIL, $envoyerATous)
            ->add(Crud::PAGE_DETAIL, $envoyerACible)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash');
            });
    }

    /**
     * Récupère la notification depuis la Request de façon fiable.
     * getContext()->getEntity() peut retourner null dans les actions custom EasyAdmin.
     */
    private function getNotificationFromRequest(Request $request, EntityManagerInterface $em): Notification
    {
        $entityId = $request->query->get('entityId');

        if (!$entityId) {
            throw new \LogicException('Impossible de récupérer l\'identifiant de la notification depuis la requête.');
        }

        $notification = $em->getRepository(Notification::class)->find($entityId);

        if (!$notification) {
            throw new \LogicException(sprintf('Notification #%s introuvable.', $entityId));
        }

        return $notification;
    }

    /**
     * Action : Envoyer à tous les scouts
     */
    public function envoyerATous(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $notification = $this->getNotificationFromRequest($request, $em);

        $this->notificationService->envoyerATous($notification);

        $this->addFlash('success', sprintf(
            '✅ La notification "%s" a été envoyée à tous les scouts.',
            $notification->getTitre()
        ));

        return $this->redirectToDetail($notification);
    }

    /**
     * Action : Envoyer au groupe ciblé
     */
    public function envoyerACible(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $notification = $this->getNotificationFromRequest($request, $em);

        if (!$notification->getCible()) {
            $this->addFlash('error', 'Aucune cible sélectionnée pour cette notification.');
            return $this->redirectToDetail($notification);
        }

        $this->notificationService->envoyerACible($notification->getCible(), $notification);

        // Récupérer le nom lisible de la cible
        $cibles = array_flip(Notification::getCiblesDisponibles());
        $nomCible = $cibles[$notification->getCible()] ?? $notification->getCible();

        $count = $notification->getUtilisateurNotifications()->count();

        $this->addFlash('success', sprintf(
            '✅ La notification "%s" a été envoyée à %d scout(s) (%s).',
            $notification->getTitre(),
            $count,
            $nomCible
        ));

        return $this->redirectToDetail($notification);
    }

    /**
     * Helper : redirection vers la page détail
     */
    private function redirectToDetail(Notification $notification): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($notification->getId())
            ->generateUrl();

        return new RedirectResponse($url);
    }


    /**
     * Personnalisation après création
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);

        $this->addFlash('success', 'La notification a été créée. Vous pouvez maintenant l\'envoyer aux scouts.');
    }
}

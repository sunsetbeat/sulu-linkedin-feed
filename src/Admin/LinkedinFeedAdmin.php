<?php declare(strict_types=1);

namespace sunsetbeat\SuluLinkedinFeed\Admin;

use sunsetbeat\SuluLinkedinFeed\Entity\LinkedinFeed;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\TogglerToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class LinkedinFeedAdmin extends Admin
{
    final public const PERMISSIONGROUP_LIST_VIEW = 'linkedin_feed';
    final public const PERMISSIONGROUP_FORM_KEY = 'linkedin_feed';
    final public const PERMISSIONGROUP_ADD_FORM_VIEW = 'sunsetbeat.linkedin_feed_add_form';
    final public const PERMISSIONGROUP_EDIT_FORM_VIEW = 'sunsetbeat.linkedin_feed_edit_form';
    final public const PERMISSIONGROUP_EDIT_FORM_PERMISSIONS = 'sunsetbeat.linkedin_feed_edit_form_permissions';

    public function __construct(
        private readonly ViewBuilderFactoryInterface $viewBuilderFactory,
        private readonly WebspaceManagerInterface $webspaceManager,
        private readonly SecurityCheckerInterface $securityChecker,
        private readonly array $linkedin_feed_config,
    ) {
    }

    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Social Media' => [
                    LinkedinFeed::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::EDIT,
                        PermissionTypes::LIVE,
                    ],
                ],
            ],
        ];
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if (!$navigationItemCollection->has('sunsetbeat.linkedin_feed.menu_name')) {
            $module = new NavigationItem('sunsetbeat.linkedin_feed.menu_name');
            $module->setPosition($this->linkedin_feed_config['menu']['main_menu']);
            $module->setIcon('fa-share-alt');
            $navigationItemCollection->add($module);
        }

        if ($this->securityChecker->hasPermission(LinkedinFeed::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            // Configure a NavigationItem with a View
            $linkedin_feed = new NavigationItem('sunsetbeat.linkedin_feed.name');
            $linkedin_feed->setPosition($this->linkedin_feed_config['menu']['sub_menu']['linkedin_feed']);
            $linkedin_feed->setView(static::PERMISSIONGROUP_LIST_VIEW);
            $navigationItemCollection->get('sunsetbeat.linkedin_feed.menu_name')->addChild($linkedin_feed);
        }

    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $locales = $this->webspaceManager->getAllLocales();

        // Configure LinkedinFeed List View
        $listToolbarActions = [];
        $formToolbarActions = [];
        if ($this->securityChecker->hasPermission(LinkedinFeed::SECURITY_CONTEXT, PermissionTypes::LIVE)) {
            $formToolbarActions[] = new ToolbarAction('sulu_admin.save');
            $formToolbarActions[] = new TogglerToolbarAction(
                'sunsetbeat.input.enable',
                'enabled',
                'enable',
                'disable',
            );
        }

        if ($this->securityChecker->hasPermission(LinkedinFeed::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            $listView = $this->viewBuilderFactory->createListViewBuilder(self::PERMISSIONGROUP_LIST_VIEW, '/sunsetbeat/linkedin_feed/:locale')
                ->setResourceKey(LinkedinFeed::RESOURCE_KEY)
                ->setListKey(self::PERMISSIONGROUP_LIST_VIEW)
                ->setTitle('sunsetbeat.linkedin_feed.name')
                ->addListAdapters(['table'])
                ->addLocales($locales)
                ->setDefaultLocale($locales[0])
                ->setAddView(static::PERMISSIONGROUP_ADD_FORM_VIEW)
                ->setEditView(static::PERMISSIONGROUP_EDIT_FORM_VIEW)
                ->addToolbarActions($listToolbarActions);
            $viewCollection->add($listView);
        }

        // Configure LinkedinFeed Edit View
        if ($this->securityChecker->hasPermission(LinkedinFeed::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $editFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(static::PERMISSIONGROUP_EDIT_FORM_VIEW, '/sunsetbeat/linkedin_feed/:locale/:id')
                ->setResourceKey(LinkedinFeed::RESOURCE_KEY)
                ->setBackView(static::PERMISSIONGROUP_LIST_VIEW)
                ->addLocales($locales)
                ->setTitleProperty('title');
            $viewCollection->add($editFormView);

            $editDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(static::PERMISSIONGROUP_EDIT_FORM_VIEW . '.details', '/details')
                ->setResourceKey(LinkedinFeed::RESOURCE_KEY)
                ->setFormKey(self::PERMISSIONGROUP_FORM_KEY)
                ->setTabTitle('sulu_admin.details')
                ->addToolbarActions($formToolbarActions)
                ->setParent(static::PERMISSIONGROUP_EDIT_FORM_VIEW);
            $viewCollection->add($editDetailsFormView);
        }
    }
}

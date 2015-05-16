<?php
/**
 * @var \Wasabi\Core\View\AppView $this
 * @var \Wasabi\Core\Model\Entity\Menu $menu
 * @var \Wasabi\Core\Model\Entity\MenuItem $menuItem
 */

$isEdit = false;
if ($this->request->params['action'] === 'add_item') {
    $this->Html->setTitle(__d('wasabi_core', 'Add a new Menu Item'));
} else {
    $this->Html->setTitle(__d('wasabi_core', 'Edit Menu Item'));
    $this->Html->setSubTitle($this->request->data('name'));
    $isEdit = true;
}
$this->Html->addAction($this->Guardian->protectedLink(
    __d('wasabi_core', 'Back to {0} Menu', [$menu->get('name')]),
    [
        'plugin' => 'Wasabi/Core',
        'controller' => 'Menus',
        'action' => 'edit',
        $menu->get('id')
    ],
    [
        'class' => 'no-icon'
    ]
));

$nameOpts = ['label' => __d('wasabi_core', 'Menu Item Name')];

if (!$isEdit) {
    $nameOpts['autofocus'] = '';
}

echo $this->Form->create($menuItem, ['class' => 'no-top-section']);
    if ($isEdit) {
        echo $this->Form->input('id', ['type' => 'hidden']);
    }
    echo $this->Form->input('name', $nameOpts);
    echo $this->Html->div('form-controls');
        echo $this->Form->button('<span>' . __d('wasabi_core', 'Save') . '</span>', ['div' => false, 'class' => 'button']);
        echo $this->Guardian->protectedLink(__d('wasabi_core', 'Cancel'), [
            'plugin' => 'Wasabi/Core',
            'controller' => 'Menus',
            'action' => 'edit',
            $menu->get('id')
        ]);
    echo $this->Html->tag('/div');
echo $this->Form->end();

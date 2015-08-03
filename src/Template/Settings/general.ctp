<?php
/**
 * @var \Wasabi\Core\View\AppView $this
 * @var \Wasabi\Core\Model\Entity\GeneralSetting $settings
 */

$this->Html->setTitle(__d('wasabi_core', 'General Settings'));
echo $this->Form->create($settings, ['context' => ['table' => 'Wasabi/Core.GeneralSettings']]);
    echo $this->Form->widget('section', [
        'title' => __d('wasabi_core', 'Instance'),
        'description' => __d('wasabi_core', 'General instance settings')
    ]);
    echo $this->Form->input('instance_name', [
        'label' => __d('wasabi_core', 'Instance Name'),
        'info' => __d('wasabi_core', 'The name of your CMS instance.')
    ]);
    echo $this->Form->widget('section', [
        'title' => __d('wasabi_core', 'Login Message'),
        'description' => __d('wasabi_core', 'An optional message that is displayed on top of the login page.')
    ]);
    echo $this->Form->input('Login__Message__show', [
        'label' => __d('wasabi_core', 'Display Login Message?'),
        'options' => [
            '0' => __d('wasabi_core', 'No'),
            '1' => __d('wasabi_core', 'Yes')
        ]
    ]);
    echo $this->Form->input('Login__Message__text', [
        'label' => __d('wasabi_core', 'Login Message Text'),
        'labelInfo' => __d('wasabi_core', 'The text of the login message.'),
        'info' => __d('wasabi_core', 'allowed Html tags: &lt;b&gt;&lt;strong&gt;&lt;a&gt;&lt;br&gt;&lt;br/&gt;'),
        'type' => 'textarea',
        'rows' => 2
    ]);
    echo $this->Form->input('Login__Message__class', [
        'label' => __d('wasabi_core', 'Login Message Class'),
        'label_info' => __d('wasabi_core', 'The CSS class that is applied to the message box.'),
        'options' => [
            'info' => 'info',
            'warning' => 'warning',
            'error' => 'error'
        ]
    ]);
    echo $this->Html->div('form-controls');
        echo $this->Form->button(__d('wasabi_core', 'Save'), ['div' => false, 'class' => 'button']);
        echo $this->Guardian->protectedLink(__d('wasabi_core', 'Cancel'), [
            'plugin' => 'Wasabi/Core',
            'controller' => 'Settings',
            'action' => 'general'
        ]);
    echo $this->Html->tag('/div');
echo $this->Form->end();

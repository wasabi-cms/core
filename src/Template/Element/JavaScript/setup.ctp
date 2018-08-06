<?php
/**
 * @var \Wasabi\Core\View\AppView $this
 * @var int $heartBeatFrequency
 */

use Cake\Core\Configure;

$options = [
    'translations' => [
        'confirmYes' => __d('wasabi_core', 'Yes'),
        'confirmNo' => __d('wasabi_core', 'No')
    ],
    'heartbeat' => $heartBeatFrequency,
    'heartbeatEndpoint' => $this->Url->build($this->Route->apiHeartbeat()),
    'cookiePath' => $this->request->getAttribute('base')
];

$debugJavascript = (Configure::read('debug') && Configure::read('debugJS'));
?>
<?= $this->Asset->js('js/wasabi' . (!$debugJavascript ? '.min' : '') .'.js', 'Wasabi/Core') ?>
<?= $this->fetch('backend-js-assets') ?>
<script>
    window.WS.configureModule('Wasabi/Core', <?= json_encode($options) ?>);
    <?= $this->fetch('backend-js'); ?>
    window.WS.boot();
</script>
<?= $this->fetch('backend-js-assets-after-init') ?>

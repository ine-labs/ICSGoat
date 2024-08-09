<?php p($l->t('Hello,')); ?>

<?php if ($_['message'] !== ''): ?>
<?php print_unescaped($_['message']); ?>


<?php endif; ?>
<?php print_unescaped($l->t('See %s on %s for more information', [$_['serverUrl'], $theme->getName()])); ?>


<?php print_unescaped($this->inc('plain.mail.footer', ['app' => 'core'])); ?>

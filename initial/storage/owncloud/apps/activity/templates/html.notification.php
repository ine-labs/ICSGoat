<?php
/** @var OCP\IL10N $l */
/** @var array $_ */
$l = $_['overwriteL10N'];
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr><td>
			<table cellspacing="0" cellpadding="0" border="0" width="600px">
				<tr>
					<td bgcolor="<?php p($theme->getMailHeaderColor());?>" width="20px">&nbsp;</td>
					<td bgcolor="<?php p($theme->getMailHeaderColor());?>">
						<img src="<?php p(\OC::$server->getURLGenerator()->getAbsoluteURL(image_path('', 'logo-mail.gif'))); ?>" alt="<?php p($theme->getName()); ?>"/>
					</td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<tr>
					<td width="20px">&nbsp;</td>
					<td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">
						<p>
							<?php print_unescaped(
	$l->t('Hello %s,', [$_['username']])
); ?>
						</p>
						<p>
							<?php print_unescaped(
	$l->t(
		'You are receiving this email because the following things happened at <a href="%s">%s</a>',
		[$_['owncloud_installation'], $theme->getName()]
	)
) ?>
						</p>
						<ul>
							<?php foreach ($_['activities'] as $activityData) {
	?>
							<li>
								<?php print_unescaped($l->t('%1$s - %2$s', $activityData)); ?>
							</li>
							<?php
} ?>
							<?php if ($_['skippedCount']) {
		?>
							<li>
								<?php print_unescaped(
			$l->n('and %n more ', 'and %n more ', $_['skippedCount'])
		); ?>
							</li>
							<?php
	} ?>
						</ul>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<td width="20px">&nbsp;</td>
					<td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">
						<?php print_unescaped($this->inc('html.mail.footer', ['app' => 'core'])); ?>
					</td>
				</tr>
			</table>
		</td></tr>
</table>

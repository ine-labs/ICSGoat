<?php /** @var $l \OCP\IL10N */ ?>
<div id='notification'></div>

<div id="emptycontent" class="hidden"></div>

<input type="hidden" name="dir" value="" id="dir">

<div class="nofilterresults hidden">
	<div class="icon-search"></div>
	<h2><?php p($l->t('No entries found in this folder')); ?></h2>
	<p></p>
</div>

<div id="controls" class="archive-crumb"></div>

<table id="filestable">
	<thead>
		<tr>
			<th id='headerName' class="hidden column-name">
				<div id="headerName-container">
					<a class="name sort columntitle" data-sort="name"><span><?php p($l->t('Name')); ?></span><span class="sort-indicator"></span></a>
				</div>
			</th>
			<th id="headerArchivedTime" class="hidden column-archived-time">
				<a id="archived-time" class="columntitle" data-sort="archived-time"><span><?php p($l->t('Archived')); ?></span><span class="sort-indicator"></span></a>
			</th>
			<th id="headerExpireTime" class="hidden column-expiring-time">
				<a id="expiring-time" class="columntitle" data-sort="expiring-time"><span><?php p($l->t('Expires')); ?></span><span class="sort-indicator"></span></a>
			</th>
			<th id="headerDate" class="hidden column-mtime">
				<a id="modified-time" class="columntitle" data-sort="mtime"><span><?php p($l->t('Last Modified')); ?></span><span class="sort-indicator"></span></a>
			</th>
		</tr>
	</thead>
	<tbody id="fileList">
	</tbody>
	<tfoot>
	</tfoot>
</table>

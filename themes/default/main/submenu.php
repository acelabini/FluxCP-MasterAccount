<?php if (!defined('FLUX_ROOT')) exit; ?>
<?php $subMenuItems = $this->getSubMenuItems(); $menus = array() ?>
<?php if (!empty($subMenuItems)): ?>
	<div id="submenu">Menu:
	<?php foreach ($subMenuItems as $menuItem):
        $menus[] = sprintf('<a href="%s" class="sub-menu-item%s">%s</a>',
			$this->url($menuItem['module'], $menuItem['action'], $params->get('id') ? ['id' => $params->get('id') ]: ''),
			$params->get('module') == $menuItem['module'] && $params->get('action') == $menuItem['action'] ? ' current-sub-menu' : '',
			htmlspecialchars($menuItem['name'])) ?>
	<?php endforeach ?>
	<?php echo implode(' / ', $menus) ?>
	</div>
<?php endif ?>

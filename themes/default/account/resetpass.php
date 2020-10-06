<?php if (!defined('FLUX_ROOT')) exit; ?>
<h2><?php echo htmlspecialchars(Flux::message('ResetPassTitle')) ?></h2>
<?php if (!empty($errorMessage)): ?>
<p class="red"><?php echo htmlspecialchars($errorMessage) ?></p>
<?php endif ?>


<?php if (!Flux::config('MasterAccount') || Flux::config('MasterAccount') && !$session->isLoggedIn()): ?>
<p><?php echo htmlspecialchars(Flux::message('ResetPassInfo')) ?></p>
<p><?php echo htmlspecialchars(Flux::message('ResetPassInfo2')) ?></p>
<form action="<?php echo $this->urlWithQs ?>" method="post" class="generic-form">
	<table class="generic-form-table">
		<?php if (count($serverNames) > 1): ?>
		<tr>
			<th><label for="login"><?php echo htmlspecialchars(Flux::message('ResetPassServerLabel')) ?></label></th>
			<td>
				<select name="login" id="login"<?php if (count($serverNames) === 1) echo ' disabled="disabled"' ?>>
				<?php foreach ($serverNames as $serverName): ?>
					<option value="<?php echo htmlspecialchars($serverName) ?>"<?php if ($params->get('server') == $serverName) echo ' selected="selected"' ?>><?php echo htmlspecialchars($serverName) ?></option>
				<?php endforeach ?>
				</select>
			</td>
			<td><p><?php echo htmlspecialchars(Flux::message('ResetPassServerInfo')) ?></p></td>
		</tr>
		<?php endif ?>
        <?php if (!Flux::config('MasterAccount')): ?>
		<tr>
			<th><label for="userid"><?php echo htmlspecialchars(Flux::message('ResetPassAccountLabel')) ?></label></th>
			<td><input type="text" name="userid" id="userid" /></td>
			<td><p><?php echo htmlspecialchars(Flux::message('ResetPassAccountInfo')) ?></p></td>
		</tr>
        <?php endif; ?>
		<tr>
			<th><label for="email"><?php echo htmlspecialchars(Flux::message('ResetPassEmailLabel')) ?></label></th>
			<td><input type="text" name="email" id="email" /></td>
			<td><p><?php echo htmlspecialchars(Flux::message('ResetPassEmailInfo')) ?></p></td>
		</tr>
		<tr>
			<td colspan="2" align="right"><input type="submit" value="<?php echo htmlspecialchars(Flux::message('ResetPassButton')) ?>" /></td>
			<td></td>
		</tr>
	</table>
</form>
<?php else: ?>
    <p>If you lost your password, you can re-set it by clicking the Send button.</p>
    <p>An e-mail will then be sent to <a href="#"><?php echo htmlspecialchars($session->account->email) ?></a> with a link allowing you to reset your password</p>
    <p>You are resetting the password of <a href="">"<?php echo $account->userid ?>"</a> account.</p>
    <form action="<?php echo $this->urlWithQs ?>" method="post" class="generic-form">
        <input type="hidden" name="what_are_you_looking_for" value="stop_what_you_are_doing">
        <input type="submit" value="<?php echo htmlspecialchars(Flux::message('ResetPassButton')) ?>" />
    </form>
<?php endif; ?>
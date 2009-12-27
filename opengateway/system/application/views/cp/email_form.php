<?

/* Default Values */

if (!isset($form)) {
	$form = array(
				'to_address' => 'customer',
				'bcc_address' => ''
			);

} ?>
<?=$this->load->view('cp/header', array('head_files' => '<link type="text/css" rel="stylesheet" href="' . site_url('js/jwysiwyg/jquery.wysiwyg.css') . '" />
<script type="text/javascript" src="' . site_url('js/jwysiwyg/jquery.wysiwyg.js') . '"></script>
<script type="text/javascript" src="' . site_url('js/form.email.js') . '"></script>'));?>
<h1><?=$form_title;?></h1>
<form class="form" id="form_email" method="post" action="<?=site_url($form_action);?>">
<fieldset>
	<legend>System Information</legend>
	<ul class="form">
		<li>
			<label for="trigger">Trigger</label>
			<select id="trigger" class="required" name="trigger">
				<option value=""></option>
				<? foreach ($triggers as $trigger) { ?>
				<option value="<?=$trigger['email_trigger_id'];?>"><?=$trigger['human_name'];?></option>
				<? } ?>
			</select>
		</li>
		<li>
			<div class="help">This system action will trigger this email.</div>
		</li>
		<li>
			<label for="plan">Plan Link</label>
			<select id="plan" name="plan">
				<option value="">Any plan or no plan at all</option>
				<option value="-1">No plans</option>
				<option value="0">All plans</option>
				<? foreach ($plans as $plan) { ?>
				<option value="<?=$plan['id'];?>">Plan: <?=$plan['name'];?></option>
				<? } ?>
			</select>
		</li>
		<li>
			<div class="help">Only send when the action relates to the plan(s) above.</div>
		</li>
	</ul>
</fieldset>
<fieldset>
	<legend>Send To</legend>
	<ul class="form">
		<li>
			<label for="to_address">Send to</label>
			<input <? if ($form['to_address'] == 'customer') { ?>checked="checked" <? } ?>type="radio" class="required" id="to_address" name="to_address" value="customer" />&nbsp;Customer&nbsp;&nbsp;&nbsp;
			<input <? if ($form['to_address'] != 'customer') { ?>checked="checked" <? } ?>type="radio" class="required" id="to_address" name="to_address" value="email" />&nbsp;<input type="text" class="text email" id="to_address_email" name="to_address_email" />
		</li>
		<li>
			<label for="bcc_address">BCC</label>
			<input <? if ($form['bcc_address'] == '') { ?>checked="checked" <? } ?>type="radio" id="bcc_address" name="bcc_address" value="" />&nbsp;None&nbsp;&nbsp;&nbsp;
			<input <? if ($form['bcc_address'] == 'client') { ?>checked="checked" <? } ?>type="radio" id="bcc_address" name="bcc_address" value="client" />&nbsp;My account email&nbsp;&nbsp;&nbsp;
			<input <? if ($form['bcc_address'] != 'client' and $form['bcc_address'] == '') { ?>checked="checked" <? } ?>type="radio" id="bcc_address" name="bcc_address" value="email" />&nbsp;<input type="text" class="text email" id="bcc_address_email" name="bcc_address_email" />
		</li>
	</ul>
</fieldset>
<fieldset>
	<legend>Send From</legend>
	<ul class="form">
		<li>
			<label for="from_name">From Name</label>
			<input type="text" class="text required" id="from_name" name="from_name" />
		</li>
		<li>
			<label for="from_email">From Address</label>
			<input type="text" class="text required email" id="from_email" name="from_email" />
		</li>
	</ul>
</fieldset>
<fieldset>
	<legend>Email</legend>
	<ul class="form">
		<li>
			<label for="email_subject" class="full">Email Subject</label>
		</li>
		<li>
			<input type="text" class="text full required" id="email_subject" name="email_subject" />
		</li>
		<li>
			<label for="email_body" class="full">Email Body</label> <a href="#" id="make_html">use HTML format</a>
			<input type="hidden" name="is_html" id="is_html" value="0" autocomplete="off" />
		</li>
		<li>
			<textarea class="full required" id="email_body" name="email_body"></textarea>
		</li>
		<li>
			<div id="email_variables">
			</div>
		</li>
	</ul>
</fieldset>
<div class="submit">
	<input type="submit" name="go_email" value="Create new email" />
</div>
<?=$this->load->view('cp/footer');?>
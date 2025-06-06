<?php
/**
 * Change sex
 *
 * @package   MyAAC
 * @author    Gesior <jerzyskalski@wp.pl>
 * @author    Slawkens <slawkens@gmail.com>
 * @copyright 2019 MyAAC
 * @link      https://my-aac.org
 */
defined('MYAAC') or die('Direct access not allowed!');

$title = 'Change Sex';
require PAGES . 'account/base.php';

if(!$logged) {
	return;
}

csrfProtect();

$sex_changed = false;
$player_id = isset($_POST['player_id']) ? (int)$_POST['player_id'] : NULL;
$new_sex = isset($_POST['new_sex']) ? (int)$_POST['new_sex'] : NULL;
if((!setting('core.account_change_character_sex')))
	echo 'You cant change your character sex';
else
{
	$points = $account_logged->getCustomField(setting('core.donate_column'));
	if(isset($_POST['changesexsave']) && $_POST['changesexsave'] == 1) {
		if($points < setting('core.account_change_character_sex_price'))
			$errors[] = 'You need ' . setting('core.account_change_character_sex_price') . ' premium points to change sex. You have <b>'.$points.'</b> premium points.';

		if(empty($errors) && !isset($config['genders'][$new_sex])) {
			$errors[] = 'This sex is invalid.';
		}

		if(empty($errors)) {
			$player = new OTS_Player();
			$player->load($player_id);

			if($player->isLoaded()) {
				$player_account = $player->getAccount();

				if($account_logged->getId() == $player_account->getId()) {
					if ($player->isDeleted()) {
						$errors[] = 'This character is deleted.';
					}

					if($player->isOnline()) {
						$errors[] = 'This character is online.';
					}

					if(empty($errors) && $player->getSex() == $new_sex)
						$errors[] = 'Sex cannot be same';

					if(empty($errors)) {
						$sex_changed = true;
						$old_sex = $player->getSex();
						$player->setSex($new_sex);

						$old_sex_str = 'Unknown';
						if(isset($config['genders'][$old_sex]))
							$old_sex_str = $config['genders'][$old_sex];

						$new_sex_str = 'Unknown';
						if(isset($config['genders'][$new_sex]))
							$new_sex_str = $config['genders'][$new_sex];

						$player->save();
						$account_logged->setCustomField(setting('core.donate_column'), $points - setting('core.account_change_character_sex_price'));
						$account_logged->logAction('Changed sex on character <b>' . $player->getName() . '</b> from <b>' . $old_sex_str . '</b> to <b>' . $new_sex_str . '</b>.');
						$twig->display('success.html.twig', array(
							'title' => 'Character Sex Changed',
							'description' => 'The character <b>' . $player->getName() . '</b> sex has been changed to <b>' . $new_sex_str . '</b>.'
						));
					}
				}
				else {
					$errors[] = 'Character is not on your account.';
				}
			}
			else {
				$errors[] = "Character with this name doesn't exist.";
			}
		}
	}

	if(!$sex_changed) {
		if(!empty($errors)) {
			$twig->display('error_box.html.twig', array('errors' => $errors));
		}
		$twig->display('account.characters.change-sex.html.twig', array(
			'players' => $account_logged->getPlayersList(false),
			'player_sex' => isset($player) ? $player->getSex() : -1,
			'points' => $points
		));
	}
}

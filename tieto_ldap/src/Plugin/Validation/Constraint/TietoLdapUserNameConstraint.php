<?php

namespace Drupal\tieto_ldap\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value is a valid user name.
 *
 * Copy of core user module's UserNameConstraint.
 *
 * @Constraint(
 *   id = "TietoLdapUserName",
 *   label = @Translation("Tieto LDAP User name", context = "Validation"),
 * )
 */
class TietoLdapUserNameConstraint extends Constraint {

  public $emptyMessage = 'You must enter a username.';
  public $spaceBeginMessage = 'The username cannot begin with a space.';
  public $spaceEndMessage = 'The username cannot end with a space.';
  public $multipleSpacesMessage = 'The username cannot contain multiple spaces in a row.';
  public $illegalMessage = 'The username contains an illegal character.';
  public $tooLongMessage = 'The username %name is too long: it must be %max characters or less.';

}

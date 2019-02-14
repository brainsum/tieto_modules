<?php

namespace Drupal\tieto_ldap\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\UserInterface;
use Egulias\EmailValidator\EmailValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TietoLdapUserNameUserName constraint.
 *
 * Copy of core user module's UserNameConstraintValidator with modifications:
 * we allow valid email address for username.
 */
class TietoLdapUserNameConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator')
    );
  }

  /**
   * TietoLdapUserNameConstraintValidator constructor.
   *
   * @param \Egulias\EmailValidator\EmailValidatorInterface $emailValidator
   *   Email validator.
   */
  public function __construct(
    EmailValidatorInterface $emailValidator
  ) {
    $this->emailValidator = $emailValidator;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!isset($items) || !$items->value) {
      $this->context->addViolation($constraint->emptyMessage);
      return;
    }
    $name = $items->first()->value;
    if (\strpos($name, ' ') === 0) {
      $this->context->addViolation($constraint->spaceBeginMessage);
    }
    if (\substr($name, -1) === ' ') {
      $this->context->addViolation($constraint->spaceEndMessage);
    }
    if (\strpos($name, '  ') !== FALSE) {
      $this->context->addViolation($constraint->multipleSpacesMessage);
    }
    if (
      \preg_match('/[^\x{80}-\x{F7} a-z0-9@+_.\'-]/i', $name)
      || \preg_match(
        // Non-printable ISO-8859-1 + NBSP.
        '/[\x{80}-\x{A0}' .
        // Soft-hyphen.
        '\x{AD}' .
        // Various space characters.
        '\x{2000}-\x{200F}' .
        // Bidirectional text overrides.
        '\x{2028}-\x{202F}' .
        // Various text hinting characters.
        '\x{205F}-\x{206F}' .
        // Byte order mark.
        '\x{FEFF}' .
        // Full-width latin.
        '\x{FF01}-\x{FF60}' .
        // Replacement characters.
        '\x{FFF9}-\x{FFFD}' .
        // NULL byte and control characters.
        '\x{0}-\x{1F}]/u',
        $name)
    ) {
      // Core username validation failed - we check if it's valid email address.
      if (!$this->emailValidator->isValid($name)) {
        $this->context->addViolation($constraint->illegalMessage);
      }
    }
    if (\mb_strlen($name) > UserInterface::USERNAME_MAX_LENGTH) {
      $this->context->addViolation($constraint->tooLongMessage, [
        '%name' => $name,
        '%max' => UserInterface::USERNAME_MAX_LENGTH,
      ]);
    }
  }

}

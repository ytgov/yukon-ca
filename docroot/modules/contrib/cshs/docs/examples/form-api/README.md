# Form API

## Use CSHS in a custom form

Refer to the example below to see how to use the CSHS in a custom form.

```php
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cshs\Element\CshsElement;
use Drupal\cshs\Component\CshsOption;

class MyForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'my_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['vehicle_model'] = [
      '#type' => CshsElement::ID,
      '#label' => $this->t('Vehicle'),
      '#required' => TRUE,
      '#labels' => [
        'Manufacturer',
        'Model',
      ],
      '#none_value' => 'missing',
      '#none_label' => 'My model is missing',
      // Do not show `My model is missing` for the first select box.
      '#no_first_level_none' => TRUE,
      '#options' => [
        'vw' => new CshsOption('Volkswagen'),
        'passat' => new CshsOption('Passat', 'vw'),
        'golf' => new CshsOption('Golf', 'vw'),
        'audi' => new CshsOption('Audi'),
        'a1' => new CshsOption('A1', 'audi'),
        'q2' => new CshsOption('Q2', 'audi'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form,FormStateInterface $form_state): void {
    \Drupal::configFactory()
      ->getEditable('vehicle')
      ->set('model', $form_state->getValue('vehicle_model'))
      ->save();
  }

}
```

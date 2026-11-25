/**
 * @file
 * Event form: while "same times" is checked, copy the first date's start and
 * end times to the other dates. The server repeats this on save (refs #1184).
 */
(function (Drupal, once) {
  const F = 'field_event_start_time';
  const input = (delta, part, key) => document.querySelector(`input[name="${F}[${delta}][${part}][${key}]"]`);

  // The row deltas in the DOM, first row first (the table is in display order).
  const deltas = () => [...new Set([...document.querySelectorAll(`input[name^="${F}["][name$="[value][date]"]`)]
    .map((el) => el.name.match(/\[(\d+)\]/)[1]))];

  const addDays = (iso, days) => {
    const d = new Date(`${iso}T00:00:00Z`);
    d.setUTCDate(d.getUTCDate() + days);
    return d.toISOString().slice(0, 10);
  };

  const sync = (on) => {
    const [first, ...rest] = deltas();
    if (first === undefined) {
      return;
    }
    const startDate = input(first, 'value', 'date').value;
    const endDate = input(first, 'end_value', 'date').value;
    const offset = startDate && endDate ? Math.round((new Date(endDate) - new Date(startDate)) / 864e5) : 0;
    rest.forEach((delta) => {
      const targets = [input(delta, 'value', 'time'), input(delta, 'end_value', 'time'), input(delta, 'end_value', 'date')];
      targets.forEach((el) => { el.readOnly = on; });
      const date = input(delta, 'value', 'date').value;
      if (on && date) {
        targets[0].value = input(first, 'value', 'time').value;
        targets[1].value = input(first, 'end_value', 'time').value;
        targets[2].value = endDate ? addDays(date, offset) : '';
      }
    });
  };

  Drupal.behaviors.eventSameTimes = {
    attach(context) {
      const box = document.querySelector('input[name="event_same_times"]');
      if (!box) {
        return;
      }
      once('eventSameTimes', 'form', context).forEach((form) => {
        form.addEventListener('input', () => sync(box.checked));
        box.addEventListener('change', () => sync(box.checked));
      });
      // New rows added with "Add another item" arrive here too.
      sync(box.checked);
    },
  };
}(Drupal, once));

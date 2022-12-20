import React from 'react';
import DrupalAttributes from 'drupal-attribute';
import card from './pattern-card-flexible.html.twig';
import cardConfig from './card_flexible.patterns.yml';
import './scss/styles.scss';

const { card: { variants } } = cardConfig;

const Template = (props) => (
  <div style={{ maxWidth: '18rem' }} dangerouslySetInnerHTML={{ __html: card({ ...props, attributes: new DrupalAttributes() }) }} />
);

export const Default = Template.bind(variants.default);
Default.args = variants.default;

export const withImage = Template.bind(variants.with_mage);
withImage.args = variants.with_mage;

export const withHeader = Template.bind(variants.with_header);
withHeader.args = variants.with_header;

export const withFooter = Template.bind(variants.with_footer);
withFooter.args = variants.with_footer;

/**
 * Storybook Definition.
 */
export default {
  component: Default,
  title: 'Molecule/card',
  argTypes: {
    imagePosition: {
      control: {
        type: 'inline-radio',
        options: ['top', 'bottom'],
      },
    },
  },
};

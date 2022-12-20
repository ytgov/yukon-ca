import React from 'react';
import DrupalAttributes from 'drupal-attribute';
import button from './pattern-button.html.twig';
import buttonConfig from './button.patterns.yml';
import './scss/styles.scss';

const { button: { variants } } = buttonConfig;

const Template = (props) => (
  <div dangerouslySetInnerHTML={{ __html: button({ ...props, attributes: new DrupalAttributes() }) }} />
);

export const Default = Template.bind(variants.default);
Default.args = variants.default;

export const primary = Template.bind(variants.primary);
primary.args = variants.primary;

export const secondary = Template.bind(variants.secondary);
secondary.args = variants.secondary;

export const success = Template.bind(variants.success);
success.args = variants.success;

export const danger = Template.bind(variants.danger);
danger.args = variants.danger;

export const warning = Template.bind(variants.warning);
warning.args = variants.warning;

export const info = Template.bind(variants.info);
info.args = variants.info;

export const light = Template.bind(variants.light);
light.args = variants.light;

export const dark = Template.bind(variants.dark);
dark.args = variants.dark;

export const link = Template.bind(variants.link);
link.args = variants.link;

/**
 * Storybook Definition.
 */
export default {
  component: Default,
  title: 'Atom/button',
  argTypes: {
    label: { control: { type: 'text' } },
    description: {
      table: { disable: true },
    },
    url: {
      table: { disable: true },
    },
    variant: {
      control: {
        type: 'select',
        options: Object.keys(variants).filter((item) => item !== 'default'),
      },
    },
    size: {
      control: {
        type: 'select',
        options: ['sm', 'md', 'lg'],
      },
    },
    outline: {
      control: {
        type: 'boolean',
      },
    },
  },
};

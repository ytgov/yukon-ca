import React from 'react';
import DrupalAttributes from 'drupal-attribute';
import blockquote from './pattern-blockquote.html.twig';
import blockquoteConfig from './blockquote.patterns.yml';
import './scss/styles.scss';

const { blockquote: { variants } } = blockquoteConfig;

const Template = (props) => (
  <div dangerouslySetInnerHTML={{ __html: blockquote({ ...props, attributes: new DrupalAttributes() }) }} />
);

export const Default = Template.bind(variants.default);
Default.args = variants.default;

/**
 * Storybook Definition.
 */
export default {
  component: Default,
  title: 'Atom/blockquote',
  argTypes: {
    content: {
      control: {
        type: 'text',
      },
    },
    attribution: {
      control: {
        type: 'text',
      },
    },
    cite: {
      control: {
        type: 'text',
      },
    },
    alignment: {
      control: {
        type: 'inline-radio',
        options: ['left', 'center', 'right', 'justify'],
      },
    },
  },
};

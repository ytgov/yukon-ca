import React from 'react';
import DrupalAttributes from 'drupal-attribute';
import breadcrumb from './pattern-breadcrumb.html.twig';
import breadcrumbConfig from './breadcrumb.patterns.yml';
import './scss/styles.scss';

const { breadcrumb: { variants } } = breadcrumbConfig;

const Template = (props) => (
  <div dangerouslySetInnerHTML={{ __html: breadcrumb({ ...props, attributes: new DrupalAttributes() }) }} />
);

export const Default = Template.bind(variants.default);
Default.args = variants.default;

/**
 * Storybook Definition.
 */
export default {
  component: Default,
  title: 'Atom/breadcrumb',
  argTypes: {
  },
};

declare namespace Cshs {
  interface Settings {
    noFirstLevelNone: boolean;
    noneLabel: string;
    noneValue: string;
    labels: string[];
  }

  interface Option {
    value: string;
    label: string;
    group?: string;
    parent?: string;
  }

  interface Plugin {
    /**
     * Destroys and reinitialize the tree.
     */
    init(): void;

    /**
     * Restores the original `select` element.
     */
    destroy(): void;
  }
}

declare namespace JQuery {
  interface TypeToTriggeredEventMap<TDelegateTarget, TData, TCurrentTarget, TTarget> {
    simplerSelectChildCreated: JQuery.TriggeredEvent<TDelegateTarget, TData, TCurrentTarget, TTarget> & {
      $wrapper: JQuery<HTMLDivElement>;
    };
    simplerSelectChildrenDeleted: JQuery.TriggeredEvent<TDelegateTarget, TData, TCurrentTarget, TTarget> & {
      $wrappers: JQuery<HTMLDivElement>;
    };
  }
}

declare interface JQuery {
  once(id: string): this;
  simplerSelect(settings: Partial<Cshs.Settings>): this;
  data(key: 'plugin_simplerSelect'): Cshs.Plugin | undefined;
}

/**
 * @see misc/drupal.es6.js
 */
declare namespace Drupal {
  type Context = HTMLDocument | HTMLElement;
  type Trigger = 'unload' | 'move' | 'serialize';

  interface Settings extends Record<string | number, unknown> {
    cshs: Partial<Cshs.Settings>;
  }

  interface Behavior {
    attach(context: Context, settings: Settings): void;
    detach?(context: Context, settings: Settings, trigger?: Trigger): void;
  }

  const behaviors: Record<string, Behavior>;
  function t(string: string, context?: Record<string, string | number>): string;
}

declare const drupalSettings: Drupal.Settings;

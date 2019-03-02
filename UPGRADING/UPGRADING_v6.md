## Upgrading to v6


### For Simple User

If you only use the `DiffHelper` and built-in `Renderer`s,
there is no breaking change for you so you do not have to do anything.


### External Breaking Changes

- The `Diff` class has been renamed to `Differ`.
  It's easy to adapt to this by changing the class name.

- Now a `Renderer` has `render()` API, but a `Differ` does not.
  But if you use those classes by yourself, it should be written like below.

  ```php
  use Jfcherng\Diff\Differ;
  use Jfcherng\Diff\Factory\RendererFactory;
  
  $differ = new Differ(explode("\n", $old), explode("\n", $new), $diffOptions);
  $renderer = RendererFactory::make($template, $templateOptions);
  $result = $renderer->render($differ); // <-- this has been changed
  ```
  
- If you call `Differ::getGroupedOpcodes()` by yourself,
  you must call `Differ::finalize()` before it.


### Internal Breaking Changes

- Now a `Renderer` should implement `protected function renderWoker(Differ $differ): string`
  rather than previous `public function render(): string`. Note that `$this->diff` no longer
  works in `Renderer`s as they are now injected as the parameter of `Renderer::renderWoker()`.
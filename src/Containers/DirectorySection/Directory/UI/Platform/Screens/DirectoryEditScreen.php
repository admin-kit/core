<?php

namespace AdminKit\Core\Containers\DirectorySection\Directory\UI\Platform\Screens;

use AdminKit\Core\Containers\DirectorySection\Directory\Models\Directory;
use AdminKit\Core\Containers\DirectorySection\Directory\Models\DirectoryTranslation;
use AdminKit\Core\Containers\DirectorySection\Directory\UI\Platform\Requests\DirectorySaveRequest;
use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class DirectoryEditScreen extends Screen
{
    public Directory $root;

    public Directory $item;

    public function query(Directory $root, Directory $item): iterable
    {
        return [
            'root' => $root,
            'item' => $item,
        ];
    }

    public function name(): ?string
    {
        return __('Edit').' '.__(Directory::RECORD_NAME).' #'.$this->item->id;
    }

    public function permission(): ?iterable
    {
        return [
            Directory::PERMISSION_READ,
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('Update'))
                ->icon('note')
                ->method('save')
                ->canSee($this->item->exists),

            Button::make(__('Remove'))
                ->icon('trash')
                ->method('remove')
                ->canSee($this->item->exists)
                ->type(Color::DANGER())
                ->confirm(__("Are you sure you want to delete entry #{$this->item->id}?")),
        ];
    }

    public function layout(): iterable
    {
        $defaultLocale = config('app.locale');
        $locales = config('translatable.locales');

        $tabs = [];
        foreach ($locales as $locale) {
            /** @var DirectoryTranslation|null $translation */
            $translation = $this->item->getTranslation($locale);
            $tabs[$locale] = [
                Layout::rows([
                    Input::make($locale.'[name]')
                        ->title(__('Name')." ($locale)")
                        ->required($locale === $defaultLocale)
                        ->placeholder(__('Enter name...'))
                        ->value($translation?->name),
                ]),
            ];
        }

        return [
            Layout::rows([
                Input::make('slug')
                    ->title(__('Slug'))
                    ->placeholder(__('enter-slug'))
                    ->value($this->item->slug),
            ]),
            Layout::tabs($tabs),
        ];
    }

    public function save(Directory $root, Directory $item, DirectorySaveRequest $request): RedirectResponse
    {
        $item->fill($request->validated())->appendToNode($root)->save();
        Alert::info(__('You have successfully saved').' '.__(Directory::NAME));

        return redirect()->route(Directory::ROUTE_CHILD_LIST, ['root' => $root->id]);
    }

    public function remove(Directory $root, Directory $item): RedirectResponse
    {
        $item->delete();
        Alert::info(__('You have successfully deleted').' '.__(Directory::NAME));

        return redirect()->route(Directory::ROUTE_CHILD_LIST, ['root' => $root->id]);
    }
}

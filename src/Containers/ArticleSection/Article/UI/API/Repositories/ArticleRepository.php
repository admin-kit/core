<?php

declare(strict_types=1);

namespace AdminKit\Core\Containers\ArticleSection\Article\UI\API\Repositories;

use AdminKit\Core\Containers\ArticleSection\Article\Models\Article;
use AdminKit\Core\Containers\ArticleSection\Article\UI\API\DTO\ArticleDTO;
use AdminKit\Core\Repositories\AbstractRepository;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ArticleRepository extends AbstractRepository implements ArticleInterface
{
    public function model(): string
    {
        return Article::class;
    }

    public function getPaginatedList(): PaginatedDataCollection
    {
        $perPage = (int) request()->query('per_page');

        $articles = QueryBuilder::for($this->model())
            ->withTranslation()
            ->allowedFilters([
                'id',
                'slug',
                'published_at',
                'created_at',
                'updated_at',
                AllowedFilter::scope('title'),
                AllowedFilter::scope('content'),
                AllowedFilter::scope('short_content'),
                'translation.title',
                'translation.content',
                'translation.short_content',
            ])
            ->allowedFields([
                'id',
                'slug',
                'published_at',
                'created_at',
                'updated_at',
                'translation.title',
                //'translation.content',
                'translation.short_content',
            ])
            ->allowedSorts(['id', 'published_at'])
            ->isPublished()
            ->isTitleNotNull()
            ->paginate($perPage);

        return ArticleDTO::collection($articles)->except('content');
    }

    /**
     * @throws Exception
     */
    public function getById(int $id): Data
    {
        $article = $this->model->findOrFail($id);

        $this->checkIsPublished($article);

        return ArticleDTO::from($article);
    }

    /**
     * @throws Exception
     */
    public function getBySlug(string $slug): Data
    {
        $article = $this->model->where('slug', $slug)->first();

        if (is_null($article)) {
            throw (new ModelNotFoundException)->setModel($this->model(), $slug);
        }

        $this->checkIsPublished($article);

        return ArticleDTO::from($article);
    }

    private function checkIsPublished(Article $article)
    {
        if (is_null($article->published_at)) {
            throw new Exception(__('Article has not been published'));
        }
    }
}

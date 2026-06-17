<?php

namespace Botble\Blog\Http\Controllers;

use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Facades\MetaBox;
use Botble\Base\Supports\Breadcrumb;
use Botble\Blog\Forms\PostForm;
use Botble\Blog\Http\Requests\PostRequest;
use Botble\Blog\Models\Post;
use Botble\Blog\Services\PostAiImageService;
use Botble\Blog\Services\StoreCategoryService;
use Botble\Blog\Services\StoreTagService;
use Botble\Blog\Tables\PostTable;
use Illuminate\Http\Request;

class PostController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/blog::base.menu_name'))
            ->add(trans('plugins/blog::posts.menu_name'), route('posts.index'));
    }

    public function index(PostTable $dataTable)
    {
        $this->pageTitle(trans('plugins/blog::posts.menu_name'));

        return $dataTable->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/blog::posts.create'));

        return PostForm::create()->renderForm();
    }

    public function store(
        PostRequest $request,
        StoreTagService $tagService,
        StoreCategoryService $categoryService
    ) {
        $form = PostForm::create()->setRequest($request)->save();

        $post = $form->getModel();

        $tagService->execute($request, $post);

        $categoryService->execute($request, $post);

        return $this
            ->httpResponse()
            ->setPreviousRoute('posts.index')
            ->setNextRoute('posts.edit', $post->getKey())
            ->withCreatedSuccessMessage();
    }

    public function edit(Post $post)
    {
        $this->pageTitle(trans('core/base::forms.edit_item', ['name' => $post->name]));

        return PostForm::createFromModel($post)->renderForm();
    }

    public function update(
        Post $post,
        PostRequest $request,
        StoreTagService $tagService,
        StoreCategoryService $categoryService,
    ) {
        $form = PostForm::createFromModel($post)
            ->setRequest($request)
            ->save();

        /**
         * @var Post $post
         */
        $post = $form->getModel();

        $tagService->execute($request, $post);

        $categoryService->execute($request, $post);

        return $this
            ->httpResponse()
            ->setPreviousRoute('posts.index')
            ->withUpdatedSuccessMessage();
    }

    public function destroy(Post $post): DeleteResourceAction
    {
        return DeleteResourceAction::make($post);
    }

    public function getWidgetRecentPosts(Request $request): BaseHttpResponse
    {
        $limit = $request->integer('paginate', 10);
        $limit = $limit > 0 ? $limit : 10;

        $posts = Post::query()
            ->with(['slugable'])
            ->latest()
            ->limit($limit)
            ->get();

        return $this
            ->httpResponse()
            ->setData(view('plugins/blog::widgets.posts', compact('posts', 'limit'))->render());
    }

    public function generateImage(
        Request $request,
        BaseHttpResponse $response,
        PostAiImageService $service
    ): BaseHttpResponse {
        $validated = $request->validate([
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'slot_type' => ['required', 'in:image,cover_image'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
        ]);

        $post = null;

        if (! empty($validated['post_id'])) {
            $post = Post::query()->find($validated['post_id']);
        }

        $result = $service->generate([
            'post_id' => $post?->getKey(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $post?->description,
            'content' => $validated['content'] ?? $post?->content,
        ], $validated['slot_type']);

        if (! ($result['ok'] ?? false)) {
            return $response
                ->setError()
                ->setMessage($result['message'] ?? 'Image generation failed.');
        }

        if ($post) {
            if ($validated['slot_type'] === 'image') {
                $post->image = $result['path'];
                $post->save();
            } else {
                MetaBox::saveMetaBoxData($post, 'cover_image', $result['path']);
            }
        }

        return $response
            ->setMessage('Image generated successfully.')
            ->setData([
                'path' => $result['path'],
                'url' => $result['url'],
                'saved' => (bool) $post,
            ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\CQRS\Items\Commands\CreateItemCommand;
use App\CQRS\Items\Commands\DeleteItemCommand;
use App\CQRS\Items\Commands\UpdateItemCommand;
use App\CQRS\Items\Handlers\CreateItemHandler;
use App\CQRS\Items\Handlers\DeleteItemHandler;
use App\CQRS\Items\Handlers\GetItemHandler;
use App\CQRS\Items\Handlers\ListItemsHandler;
use App\CQRS\Items\Handlers\UpdateItemHandler;
use App\CQRS\Items\Queries\GetItemQuery;
use App\CQRS\Items\Queries\ListItemsQuery;
use App\DTO\Items\ItemsIndexDTO;
use App\Http\Requests\Items\IndexItemsRequest;
use App\Http\Requests\Items\StoreItemRequest;
use App\Http\Requests\Items\UpdateItemRequest;
use App\Models\Item;

class ItemController extends Controller
{
    public function index(IndexItemsRequest $request)
    {
        $dto = ItemsIndexDTO::fromRequest($request, $request->validated());
        $page = app(ListItemsHandler::class)->handle(new ListItemsQuery($dto));
        return response()->json($page->toArray());
    }

    public function store(StoreItemRequest $request)
    {
        $item = app(CreateItemHandler::class)->handle(new CreateItemCommand($request->validated()));
        return response()->json(['data' => $item], 201);
    }

    public function show(Item $item)
    {
        $found = app(GetItemHandler::class)->handle(new GetItemQuery($item));
        return response()->json(['data' => $found]);
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $updated = app(UpdateItemHandler::class)->handle(new UpdateItemCommand($item, $request->validated()));
        return response()->json(['data' => $updated]);
    }

    public function destroy(Item $item)
    {
        app(DeleteItemHandler::class)->handle(new DeleteItemCommand($item));
        return response()->json([], 204);
    }
}

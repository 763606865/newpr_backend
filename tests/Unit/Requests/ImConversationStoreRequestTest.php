<?php

namespace Tests\Unit\Requests;

use App\Enums\ImConversationType;
use App\Rc\Requests\ImConversationStoreRequest;
use Illuminate\Support\Facades\Validator;
use Sqids\Sqids;
use Tests\TestCase;

class ImConversationStoreRequestTest extends TestCase
{
    public function test_authorize_allows_authenticated_route_layer_to_handle_access(): void
    {
        $this->assertTrue((new ImConversationStoreRequest)->authorize());
    }

    public function test_type_must_be_valid_im_conversation_type(): void
    {
        $request = new ImConversationStoreRequest;

        $this->assertTrue(Validator::make([
            'type' => ImConversationType::Single->value,
        ], $request->rules())->passes());

        $this->assertFalse(Validator::make([
            'type' => 'invalid',
        ], $request->rules())->passes());
    }

    public function test_members_are_optional_at_request_rule_level(): void
    {
        $request = new ImConversationStoreRequest;

        $this->assertTrue(Validator::make([
            'type' => ImConversationType::Chatroom->value,
        ], $request->rules())->passes());

        $this->assertTrue(Validator::make([
            'type' => ImConversationType::Single->value,
            'members' => [
                ['external_user_id' => (new Sqids(minLength: 32))->encode([1])],
            ],
        ], $request->rules())->passes());
    }
}

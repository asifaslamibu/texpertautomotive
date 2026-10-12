<?php

namespace App\Support;

use App\Mail\ContactMessageMail;
use App\Models\Enquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotAssistant
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = trim((string) (Setting::get('chatbot_api_key') ?: config('services.groq.key')));
        $this->model = Setting::get('chatbot_model') ?: 'llama-3.3-70b-versatile';
    }

    public static function enabled(): bool
    {
        return (bool) Setting::get('chatbot_enabled')
            && trim((string) (Setting::get('chatbot_api_key') ?: config('services.groq.key'))) !== '';
    }

    /**
     * Run one assistant turn over the conversation.
     * @param  array  $messages  [['role'=>'user'|'assistant','content'=>string], ...]
     */
    public function reply(array $messages): string
    {
        if ($this->apiKey === '') {
            return 'The assistant is not configured yet. Please contact us at '.Setting::get('contact_email', 'sales@texpertdeals.us').'.';
        }

        // OpenAI-style messages: system first, then the conversation
        $convo = [['role' => 'system', 'content' => $this->systemPrompt()]];
        foreach ($messages as $m) {
            $convo[] = ['role' => $m['role'], 'content' => (string) $m['content']];
        }

        $useTools = true;
        for ($i = 0; $i < 5; $i++) {
            $data = $this->callApi($convo, $useTools);
            if ($data === null) {
                return 'Sorry, I had trouble responding just now. Please try again, or email us at '.Setting::get('contact_email', 'sales@texpertdeals.us').'.';
            }

            $msg = $data['choices'][0]['message'] ?? [];
            $toolCalls = $msg['tool_calls'] ?? [];
            $content = (string) ($msg['content'] ?? '');

            if (empty($toolCalls)) {
                // Some models emit tool calls as inline text — e.g.
                // <function=search_products>{"query":"..."}</function> — instead of
                // using native tool calling. Detect, run them, and continue so the raw
                // markup is never shown to the customer.
                $inline = $this->extractInlineToolCalls($content);
                if (! empty($inline)) {
                    $results = [];
                    foreach ($inline as $call) {
                        $results[] = $call['name'].' result: '.$this->runTool($call['name'], $call['args']);
                    }
                    $convo[] = ['role' => 'assistant', 'content' => 'One moment while I look that up.'];
                    $convo[] = ['role' => 'user', 'content' =>
                        "Tool results below. Write a short, helpful reply for the customer using ONLY this data. "
                        ."For each product include its name and its full URL on the same line so it is clickable. "
                        ."Never output <function> tags or JSON.\n\n".implode("\n", $results)];
                    $useTools = false; // force a plain text answer on the next turn
                    continue;
                }

                $text = trim($this->stripToolMarkup($content));

                return $text !== '' ? $text : 'Sorry, I did not catch that. Could you rephrase?';
            }

            // keep the assistant's tool-call turn, then answer each call
            $convo[] = ['role' => 'assistant', 'content' => $content ?: null, 'tool_calls' => $toolCalls];
            foreach ($toolCalls as $tc) {
                $fn = $tc['function']['name'] ?? '';
                $args = json_decode($tc['function']['arguments'] ?? '{}', true) ?: [];
                $convo[] = [
                    'role' => 'tool',
                    'tool_call_id' => $tc['id'] ?? '',
                    'content' => $this->runTool($fn, (array) $args),
                ];
            }
        }

        return 'Let me connect you with our team — please email '.Setting::get('contact_email', 'sales@texpertdeals.us').' and we\'ll help right away.';
    }

    private function callApi(array $messages, bool $useTools = true): ?array
    {
        try {
            $payload = [
                'model' => $this->model,
                'max_tokens' => 800,
                'temperature' => 0.3,
                'messages' => $messages,
            ];
            if ($useTools) {
                $payload['tools'] = $this->tools();
                $payload['tool_choice'] = 'auto';
            }

            $resp = Http::withToken($this->apiKey)
                ->timeout(40)
                ->post('https://api.groq.com/openai/v1/chat/completions', $payload);

            if (! $resp->successful()) {
                Log::warning('Chatbot (Groq) API error: '.$resp->status().' '.$resp->body());

                return null;
            }

            return $resp->json();
        } catch (\Throwable $e) {
            Log::warning('Chatbot (Groq) API exception: '.$e->getMessage());

            return null;
        }
    }

    private function systemPrompt(): string
    {
        $site = Setting::get('site_name', 'Texpert Deals');
        $phone = Setting::get('contact_phone', '+1-254-358-0017');
        $email = Setting::get('contact_email', 'sales@texpertdeals.us');
        $addr = Setting::get('contact_address', '2990 Congressman Ln, Dallas, TX 75220');

        return <<<PROMPT
You are the friendly customer-support assistant for {$site}, an online HVAC/R, plumbing, tools and electrical wholesale store based in Dallas, TX (USA).

Only help with THIS store: products, orders, shipping, returns, business hours, and contact. Politely steer any off-topic chat back to how you can help with the store.

Store facts:
- Shipping: 3–5 business days on 100,000+ in-stock items across the US.
- Returns: standard return policy applies — point customers to the Return Policy page for details.
- Business hours: Monday–Friday, 9:00 AM – 6:00 PM EST.
- Contact: {$phone} · {$email} · {$addr}. Net-30 terms are available for verified contractors.

Tools — use them, never guess:
- search_products: use whenever a customer asks if something is in stock, its price, or to find products. Base your answer only on the results, and share the product link when helpful.
- get_order_status: to check an order, first ask the customer for BOTH the order number (looks like TD-01014) and the email on the order, then call it.
- create_support_ticket: if the customer wants a human, has a complex/wholesale request, or you cannot help, collect their name, email and a short message, then call it. Tell them our team will email them back.

Formatting products: when you list products, use a Markdown bullet list — one product per line as `- [Product name](full-url) — price`. Always use the exact URL returned by search_products, with nothing (no comma or period) attached to it.

Never write tool or function calls as text (for example `<function=...>` or JSON). Only call tools through the proper mechanism; if you cannot, just answer in plain words.

Style: concise (2–4 short sentences), warm and professional. Never invent product availability, prices, or order details. Reply in English.
PROMPT;
    }

    private function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products',
                    'description' => 'Search the store catalog by product name, brand or SKU. Returns matching products with price and stock status.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'What to search for, e.g. "Navac vacuum pump" or a SKU.'],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_order_status',
                    'description' => 'Look up the status of a customer order. Requires the order number and the email on the order.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'order_number' => ['type' => 'string', 'description' => 'The order number, e.g. TD-01014.'],
                            'email' => ['type' => 'string', 'description' => 'The email address used on the order.'],
                        ],
                        'required' => ['order_number', 'email'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_support_ticket',
                    'description' => 'Send the customer\'s question to the human support team by email. Use for human handoff, wholesale/bulk requests, or anything you cannot resolve.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => 'Customer name.'],
                            'email' => ['type' => 'string', 'description' => 'Customer email for the reply.'],
                            'message' => ['type' => 'string', 'description' => 'A clear summary of what the customer needs.'],
                        ],
                        'required' => ['name', 'email', 'message'],
                    ],
                ],
            ],
        ];
    }

    /** Pull tool calls a model wrongly emitted as inline text, e.g. <function=search_products>{"query":"x"}</function>. */
    private function extractInlineToolCalls(string $content): array
    {
        $calls = [];
        if (preg_match_all('/<function\s*=\s*([a-zA-Z_][\w]*)\s*>\s*(\{.*?\})/s', $content, $m, PREG_SET_ORDER)) {
            foreach ($m as $set) {
                $args = json_decode($set[2], true);
                $calls[] = ['name' => $set[1], 'args' => is_array($args) ? $args : []];
            }
        }

        return array_slice($calls, 0, 6);
    }

    /** Last-resort scrub so leaked tool/function markup never reaches the customer. */
    private function stripToolMarkup(string $s): string
    {
        $s = preg_replace('/<function\s*=\s*[\w]+\s*>.*?(<\/function>|$)/s', '', $s);
        $s = preg_replace('/<\/?(function|tool_call|tool_calls)[^>]*>/i', '', $s);

        return trim((string) $s);
    }

    private function runTool(string $name, array $input): string
    {
        return match ($name) {
            'search_products' => $this->searchProducts((string) ($input['query'] ?? '')),
            'get_order_status' => $this->getOrderStatus((string) ($input['order_number'] ?? ''), (string) ($input['email'] ?? '')),
            'create_support_ticket' => $this->createTicket($input),
            default => 'Unknown tool.',
        };
    }

    private function searchProducts(string $q): string
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return 'Please provide a product name or SKU to search.';
        }

        // break the phrase into meaningful words so "Navac vacuum pumps" matches even
        // though no single product name contains that exact phrase.
        $stop = ['the', 'a', 'an', 'do', 'you', 'have', 'has', 'is', 'are', 'what', 'whats', 'price', 'cost',
            'in', 'stock', 'for', 'of', 'and', 'with', 'me', 'i', 'need', 'want', 'looking', 'any', 'some',
            'please', 'can', 'get', 'buy', 'sell', 'available', 'your', 'my', 'to', 'on', 'or'];
        $tokens = collect(preg_split('/[^a-z0-9]+/i', mb_strtolower($q)))
            ->filter(fn ($t) => mb_strlen($t) >= 2 && ! in_array($t, $stop, true))
            ->map(fn ($t) => (mb_strlen($t) >= 4 && str_ends_with($t, 's')) ? rtrim($t, 's') : $t) // crude de-plural
            ->unique()->take(6)->values();
        if ($tokens->isEmpty()) {
            $tokens = collect([$q]);
        }

        $run = function ($tokens) {
            $query = Product::query()->where('published', true);
            foreach ($tokens as $t) {
                $query->where(fn ($w) => $w->where('name', 'like', "%{$t}%")
                    ->orWhere('brand', 'like', "%{$t}%")
                    ->orWhere('sku', 'like', "%{$t}%"));
            }

            return $query->orderByDesc('in_stock')->take(6)
                ->get(['name', 'slug', 'brand', 'sku', 'price', 'in_stock']);
        };

        $products = $run($tokens);
        // if the strict AND search finds nothing, retry with the single most distinctive word
        if ($products->isEmpty() && $tokens->count() > 1) {
            $best = $tokens->sortByDesc(fn ($t) => mb_strlen($t))->first();
            $products = $run(collect([$best]));
        }

        if ($products->isEmpty()) {
            return json_encode(['found' => 0, 'message' => 'No matching products found in the catalog.']);
        }

        return json_encode([
            'found' => $products->count(),
            'products' => $products->map(fn ($p) => [
                'name' => $p->name,
                'brand' => $p->brand,
                'sku' => $p->sku,
                'price' => '$'.number_format((float) $p->price, 2),
                'in_stock' => (bool) $p->in_stock,
                'url' => route('products.show', $p->slug),
            ])->all(),
        ], JSON_UNESCAPED_SLASHES);
    }

    private function getOrderStatus(string $number, string $email): string
    {
        $number = trim($number);
        $order = Order::where('order_number', $number)->with('customer')->first();

        if (! $order) {
            return json_encode(['found' => false, 'message' => 'No order found with that number.']);
        }
        if ($email !== '' && $order->customer && strcasecmp(trim($email), (string) $order->customer->email) !== 0) {
            return json_encode(['found' => false, 'message' => 'The email does not match our records for that order number.']);
        }

        return json_encode([
            'found' => true,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'placed_on' => optional($order->placed_at)->format('M j, Y'),
            'items' => $order->items()->count(),
            'total' => '$'.number_format((float) $order->total, 2),
        ], JSON_UNESCAPED_SLASHES);
    }

    private function createTicket(array $input): string
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            return 'Please collect the customer\'s name, a valid email, and their message before creating a ticket.';
        }

        try {
            Enquiry::create([
                'type' => 'chatbot',
                'name' => $name,
                'email' => $email,
                'subject' => 'Chatbot support request',
                'message' => $message,
            ]);

            $data = ['name' => $name, 'email' => $email, 'phone' => null, 'subject' => 'Chatbot support request', 'message' => $message];
            Notify::send(
                adminMailable: new ContactMessageMail($data),
                customerEmail: null,
                confirmation: null,
                logLabel: 'Chatbot support request',
                logData: $data,
            );

            return json_encode(['created' => true, 'message' => "Ticket created. Tell the customer our team will email {$email} shortly."]);
        } catch (\Throwable $e) {
            Log::warning('Chatbot ticket failed: '.$e->getMessage());

            return json_encode(['created' => false, 'message' => 'Could not create the ticket; ask the customer to email us directly.']);
        }
    }
}

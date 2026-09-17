<?php

namespace CXEngine\ExpertStatistics\Livewire\Ai;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Concerns\RequiresExpertStatisticsActivation;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Full-page AI chat assistant, ported from bluerocktelclients'
 * App\Filament\Pages\Ai\AiChatPage. Only the conversational core was
 * ported (send / history / delete) — the scheduled-report suggestion
 * flow that page also offered is out of scope for this port.
 */
class AiChat extends Component
{
    use AuthorizesExpertStatisticsAccess;
    use RequiresExpertStatisticsActivation;

    /** @var array<int, array<string, mixed>> */
    public array $conversations = [];

    public ?string $conversationUuid = null;

    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    public string $inputMessage = '';

    public bool $isLoading = false;

    public bool $autoSend = false;

    public ?string $errorMessage = null;

    /**
     * Accepts a `?send=` query string so other pages (e.g. the AI Dashboard's
     * Quick Actions) can deep-link straight into a pre-filled, auto-sent
     * message instead of only opening an empty chat.
     */
    public function mount(): void
    {
        $this->loadHistory();

        $prefill = trim((string) request()->query('send', ''));

        if ($prefill !== '') {
            $this->inputMessage = $prefill;
            $this->autoSend = true;
        }
    }

    public function loadHistory(): void
    {
        try {
            $result = app(ExpertStatisticsService::class)->getAiChatHistory();
            $this->conversations = array_values($result);
        } catch (\Throwable) {
            $this->conversations = [];
        }
    }

    public function selectConversation(string $uuid): void
    {
        $this->conversationUuid = $uuid;
        $this->messages = [];
        $this->inputMessage = '';
        $this->errorMessage = null;
        $this->isLoading = true;

        try {
            $result = app(ExpertStatisticsService::class)->getAiChatHistoryItem($uuid);
            $this->messages = $this->normalizeMessages($result['messages'] ?? []);
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        } finally {
            $this->isLoading = false;
        }
    }

    public function newConversation(): void
    {
        $this->conversationUuid = null;
        $this->messages = [];
        $this->inputMessage = '';
        $this->errorMessage = null;
    }

    public function sendMessage(): void
    {
        $message = trim($this->inputMessage);

        if ($message === '' || $this->isLoading) {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $message, 'type' => 'text'];
        $this->inputMessage = '';
        $this->isLoading = true;

        try {
            $result = app(ExpertStatisticsService::class)->sendAiChatMessage($message, $this->conversationUuid);

            $this->conversationUuid = $result['conversation_id'] ?? $this->conversationUuid;

            $this->messages[] = $this->normalizeAssistantMessage($result['response'] ?? []);
            $this->errorMessage = null;

            $this->loadHistory();
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
            array_pop($this->messages);
        } finally {
            $this->isLoading = false;
        }
    }

    public function useSuggestion(string $suggestion): void
    {
        $this->inputMessage = $suggestion;
        $this->sendMessage();
    }

    public function clearHistory(): void
    {
        try {
            app(ExpertStatisticsService::class)->deleteAiChatHistory();
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');

            return;
        }

        $this->conversations = [];
        $this->newConversation();
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.ai.chat');
    }

    /**
     * @param  array<int, array<string, mixed>>|string  $raw
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMessages(array|string $raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?? [];
        }

        return array_map(function (array $msg): array {
            if (($msg['role'] ?? '') === 'user') {
                return ['role' => 'user', 'content' => $msg['content'] ?? '', 'type' => 'text'];
            }

            $content = $msg['content'] ?? null;
            $response = is_string($content)
                ? (json_decode($content, true) ?? [])
                : ($msg['response'] ?? $msg);

            return $this->normalizeAssistantMessage($response);
        }, $raw);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function normalizeAssistantMessage(array $response): array
    {
        return [
            'role' => 'assistant',
            'type' => $response['type'] ?? 'text',
            'title' => $response['title'] ?? null,
            'explanation' => $response['explanation'] ?? null,
            'data' => $response['data'] ?? null,
            'insights' => $response['insights'] ?? [],
            'follow_up_suggestions' => $response['follow_up_suggestions'] ?? [],
        ];
    }
}

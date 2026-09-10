<?php

namespace CXEngine\ExpertStatistics\Livewire\Ai;

use CXEngine\ExpertStatistics\Concerns\AuthorizesExpertStatisticsAccess;
use CXEngine\ExpertStatistics\Services\ExpertStatisticsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Small persistent AI chat widget (collapsed bubble that expands into a
 * mini chat), ported from bluerocktelclients' App\Livewire\Ai\AiFloatingChat.
 * Reuses the same sendAiChatMessage/history service methods as AiChat, in a
 * compact two-tab UI (chat + history). Not auto-injected anywhere in this
 * package — the host app is expected to `@livewire()` it explicitly wherever
 * it wants the bubble to appear.
 */
class AiFloatingChat extends Component
{
    use AuthorizesExpertStatisticsAccess;

    public bool $isOpen = false;

    public bool $isExpanded = false;

    public string $tab = 'chat';

    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    public string $inputMessage = '';

    public ?string $conversationUuid = null;

    public bool $isSending = false;

    /** @var array<int, array<string, mixed>> */
    public array $conversations = [];

    public bool $historyLoaded = false;

    public ?string $errorMessage = null;

    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;
        $this->errorMessage = null;

        if ($this->isOpen && $this->tab === 'history' && ! $this->historyLoaded) {
            $this->loadHistory();
        }
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function toggleExpand(): void
    {
        $this->isExpanded = ! $this->isExpanded;
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->errorMessage = null;

        if ($tab === 'history' && ! $this->historyLoaded) {
            $this->loadHistory();
        }
    }

    public function loadHistory(): void
    {
        try {
            $result = app(ExpertStatisticsService::class)->getAiChatHistory();
            $this->conversations = array_values($result);
            $this->historyLoaded = true;
        } catch (\Throwable) {
            $this->conversations = [];
        }
    }

    public function selectConversation(string $uuid): void
    {
        $this->tab = 'chat';
        $this->conversationUuid = $uuid;
        $this->messages = [];
        $this->inputMessage = '';
        $this->errorMessage = null;
        $this->isSending = true;

        try {
            $result = app(ExpertStatisticsService::class)->getAiChatHistoryItem($uuid);
            $this->messages = $this->normalizeMessages($result['messages'] ?? []);
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
        } finally {
            $this->isSending = false;
        }
    }

    public function newConversation(): void
    {
        $this->tab = 'chat';
        $this->conversationUuid = null;
        $this->messages = [];
        $this->inputMessage = '';
    }

    public function sendMessage(): void
    {
        $message = trim($this->inputMessage);

        if ($message === '' || $this->isSending) {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $message, 'type' => 'text'];
        $this->inputMessage = '';
        $this->isSending = true;

        try {
            $result = app(ExpertStatisticsService::class)->sendAiChatMessage($message, $this->conversationUuid);

            $this->conversationUuid = $result['conversation_id'] ?? $this->conversationUuid;

            $this->messages[] = $this->normalizeAssistantMessage($result['response'] ?? []);
            $this->errorMessage = null;

            $this->historyLoaded = false;
        } catch (\Throwable) {
            $this->errorMessage = __('expert-statistics::pbx.expert_statistics.error_loading');
            array_pop($this->messages);
        } finally {
            $this->isSending = false;
        }
    }

    public function useSuggestion(string $suggestion): void
    {
        $this->inputMessage = $suggestion;
        $this->sendMessage();
    }

    public function render(): View
    {
        return view('expert-statistics::livewire.ai.floating-chat');
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

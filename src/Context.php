<?php

namespace BuhoyCoder\VK;

class Context
{
	public $vk;

	private $data = [];

	private $cache = [];
	
	public function __construct(VkApi $vk, array $data)
	{
		$this->vk   = $vk;
		$this->data = $data;
	}
	
	public function getData()
	{
		return $this->data;
	}
	
	public function getType()
	{
		if (array_key_exists('type', $this->cache)) {
			return $this->cache['type'];
		}

		return $this->cache['type'] = $this->getData()['type'];
	}
	
	public function getObject()
	{
		if (array_key_exists('object', $this->cache)) {
			return $this->cache['object'];
		}

		return $this->cache['object'] = $this->getData()['object'];
	}
	
	public function getGroupId()
	{
		if (array_key_exists('group_id', $this->cache)) {
			return $this->cache['group_id'];
		}

		return $this->cache['group_id'] = $this->getData()['group_id'];
	}
	
	public function getMessage()
	{
		if (array_key_exists('message', $this->cache)) {
			return $this->cache['message'];
		}

		return $this->cache['message'] = $this->getObject()['message'];
	}
	
	public function getPeerId()
	{
		if (array_key_exists('peer_id', $this->cache)) {
			return $this->cache['peer_id'];
		}

		return $this->cache['peer_id'] = $this->getMessage()['peer_id'];
	}
	
	public function getFromId()
	{
		if (array_key_exists('from_id', $this->cache)) {
			return $this->cache['from_id'];
		}

		return $this->cache['from_id'] = $this->getMessage()['from_id'];
	}
	
	public function getChatId()
	{
		if (array_key_exists('chat_id', $this->cache)) {
			return $this->cache['chat_id'];
		}

		return $this->cache['chat_id'] = ($this->getPeerId() > 2e9) ? $this->getPeerId() - 2e9 : 0;
	}
	
	public function getText()
	{
		if (array_key_exists('text', $this->cache)) {
			return $this->cache['text'];
		}

		$text = $this->getMessage()['text'];

		if ($this->isChat()) {
			$text = preg_replace('/\[(?:club|id).+\|.+\]/u', '', $text);
		}

		return $this->cache['text'] = trim($text);
	}
	
	public function getPayload()
	{
		if (array_key_exists('payload', $this->cache)) {
			return $this->cache['payload'];
		}

		return $this->cache['payload'] = isset($this->getMessage()['payload']) 
			? json_decode($this->getMessage()['payload'], true)
			: null;
	}
	
	public function getAction()
	{
		if (array_key_exists('action', $this->cache)) {
			return $this->cache['action'];
		}

		return $this->cache['action'] = !empty($this->getMessage()['action']) ? $this->getMessage()['action'] : null;
	}

	public function getCommand()
	{
		return (array_key_exists('command', $this->cache)) ? $this->cache['command'] : null;
	}
	
	public function markAsReadMessage()
	{
		if ($this->getType() !== EventType::MESSAGE_NEW) {
			return false;
		}

		return $this->vk->api('messages.markAsRead', [
			'peer_id' => $this->getPeerId(),
			'group_id' => $this->getGroupId()
		]);
	}
	
	public function replyMessage(string $message = '', array $params = [])
	{
		if ($this->getType() !== EventType::MESSAGE_NEW) {
			return;
		}
		
		$params['message'] = $message;
		$params['peer_id'] = $this->getPeerId();
		
		return $this->vk->sendMessage($params);
	}
	
	public function reply(string $message = '', array $params = [])
	{
		return $this->replyMessage($message, $params);
	}
	
	public function isChat() : bool
	{
		return ($this->getType() !== EventType::MESSAGE_NEW) ? false : $this->getPeerId() > 2e9;
	}

	public function onPayload(string $name, callable $handler)
	{
		if ($this->getType() !== EventType::MESSAGE_NEW) {
			return;
		}

		if (!isset($this->getPayload()['command'])) {
			return;
		}

		if ($this->getPayload()['command'] !== $name) {
			return;
		}
		
		$handler($this);
		exit;
	}
	
	public function onCommand(string $command, callable $handler)
	{
		if ($this->getType() !== EventType::MESSAGE_NEW || $this->getText() === null) {
			return;
		}

		$oneCommands   = explode(' ', $command);
		$twoCommands   = explode(' ', mb_strtolower($this->getText()));
		$countCommands = count($oneCommands);

		if ($countCommands > 1) {
			for ($i = 0; $i < $countCommands; $i++) { 
				$isCommand = $oneCommands[$i] === $twoCommands[$i];

				if ($isCommand == false) {
					break;
				}
			}
		} else {
			$isCommand = $oneCommands[0] === $twoCommands[0];
		}
		
		if ($isCommand) {
			$lengthCommand = mb_strlen($command);
			$this->cache['command'] = trim(mb_substr($this->getText(), 0, $lengthCommand));
			$this->cache['text']    = trim(mb_substr($this->getText(), $lengthCommand));

			$handler($this);
			exit;
		}
	}
	
	public function onText(string $text, callable $handler, bool $case_sensitive = false)
	{
		if ($this->getType() !== EventType::MESSAGE_NEW || $this->getText() === null) {
			return;
		}

		$name_func = ($case_sensitive) ? 'mb_strpos' : 'mb_stripos';
		$pos       = $name_func($this->getText(), $text);

		if ($pos !== false) {
			$handler($this);
			exit;
		}
	}

	public function isMessagesFromGroupAllowed(int $user_id)
	{
		return $this->vk->isMessagesFromGroupAllowed($this->getGroupId(), $user_id);
	}
}

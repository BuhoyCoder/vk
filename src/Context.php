<?php

namespace BuhoyCoder\VK;

class Context
{
	private $data = [];
	
	public $vk;

	private $cache = [];
	
	public function __construct(VkApi $vk, array $data)
	{
		$this->data = $data;
		$this->vk = $vk;
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

	public function getCommand()
	{
		return (array_key_exists('command', $this->cache)) ? $this->cache['command'] : null;
	}
	
	public function markAsReadMessage()
	{
		if ($this->getType() !== 'message_new') {
			return false;
		}

		return $this->vk->api('messages.markAsRead', [
			'peer_id' => $this->getPeerId(),
			'group_id' => $this->getGroupId()
		]);
	}
	
	public function getOnlineStatusGroup()
	{
		return $this->vk->api('groups.getOnlineStatus', [
			'group_id' => $this->getGroupId()
		]);
	}

	public function enableOnlineGroup()
	{
		$status = $this->getOnlineStatusGroup()['status'];

		if ($status === 'none') {
			return $this->vk->api('groups.enableOnline', [
				'group_id' => $this->getGroupId()
			]);
		} else if ($status === 'online') {
			return true;
		}

		return false;
	}
	
	public function replyMessage(string $message = '', array $params = [])
	{
		if ($this->getType() !== 'message_new') {
			return;
		}
		
		$params['message'] = $message;
		$params['peer_id'] = $this->getPeerId();
		
		return $this->vk->sendMessage($params);
	}
	
	public function isChat() : bool
	{
		return ($this->getType() !== 'message_new') ? false : $this->getPeerId() > 2e9;
	}

	public function onPayload(string $name, callable $handler)
	{
		if ($this->getType() !== 'message_new') {
			return;
		}

		if (empty($this->getPayload()['command'])) {
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
		if ($this->getType() !== 'message_new' || $this->getText() === NULL) {
			return;
		}

		$oneCommands = explode(' ', $command);
		$twoCommands = explode(' ', mb_strtolower($this->getText()));
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
			$this->cache['command'] = trim(mb_substr($this->getText(), 0, mb_strlen($command)));
			$this->cache['text']    = trim(mb_substr($this->getText(), mb_strlen($command)));

			$handler($this);
			exit;
		}
	}
}

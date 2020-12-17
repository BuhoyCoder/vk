<?php

namespace BuhoyCoder\VK;

class VkApi
{
	private $api_version = '5.110';

	private $api_token;
	
	private $ch;

	private $params_default = [
		'messages.send' => [
			'random_id' => 0
		]
	];

	public function __construct(string $api_token)
	{
		$this->api_token = $api_token;
		
		$this->ch = curl_init();
	}
	
	public function __destruct()
	{
		curl_close($this->ch);
	}
	
	public function setApiVersion()
	{
		$this->api_version = $api_version;
	}
	
	public function api(string $method, array $params = [])
	{
		if ( !array_key_exists('access_token', $params)) {
			$params['access_token'] = $this->api_token;
		}

		if ( !array_key_exists('v', $params)) {
			$params['v'] = $this->api_version;
		}

		if (array_key_exists($method, $this->params_default)) {
			$params = array_merge($params, $this->params_default[$method]);
		}

		return $this->request($method, $params);
	}
	
	private function request(string $method, array $params = [])
	{
		curl_setopt_array($this->ch, [
			CURLOPT_URL => 'https://api.vk.com/method/' . $method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $params
		]);
		
		return $this->parseResponse(curl_exec($this->ch));
	}
	
	private function parseResponse($response)
	{
		$decode_body = json_decode($response, true);

		if ($decode_body === NULL || !is_array($decode_body)) {
			$decode_body = [];
		}

		if (array_key_exists('response', $decode_body)) {
			return $decode_body['response'];
		} else if (array_key_exists('error', $decode_body)) {
			return $decode_body['error'];
		}

		return $decode_body;
	}

	public function getAlias($id, $n = null)
	{
		if ( ! is_numeric($id)) {
			$object = $this->api('utils.resolveScreenName', ['screen_name' => $id]);
			$id = ($object['type'] == 'group') ? -$object['object_id'] : (($object['type'] == 'user') ? $object['object_id'] : 0);
		}

		return ($id < 0) ? $this->getGroupAlias($id, $n) : $this->getUserAlias($id, $n);
	}

	public function getUserAlias($userId, $n = null)
	{
		if ( ! is_numeric($userId)) {
			$object = $this->api('utils.resolveScreenName', ['screen_name' => $userId]);
			$userId = ($object['type'] == 'user') ? $object['object_id'] : 0;
		}

		if (isset($n)) {
			if (is_string($n)) {
				return "@id{$userId} ({$n})";
			} else {
				$userInfo = $this->api('users.get', ['user_ids' => $userId])[0];
				return "@id{$userId}" . (($n) ? "({$userInfo['first_name']} {$userInfo['last_name']})" : "({$userInfo['first_name']})");
			}
		} else {
			return '@id' . $userId;
		}
	}

	public function getGroupAlias($groupId, $n = null)
	{
		if ( ! is_numeric($groupId)) {
			$object = $this->api('utils.resolveScreenName', ['screen_name' => $groupId]);
			$groupId = ($object['type'] == 'group') ? $object['object_id'] : 0;
		}

		if (isset($n)) {
			if (is_string($n)) {
				return "@club{$groupId} ({$n})";
			} else {
				$groupInfo = $this->api('groups.getById', ['group_id' => $groupId])[0];
				return "@club{$groupId} ({$groupInfo['name']})";
			}
		} else {
			return '@club' . $groupId;
		}
	}

	public function setDefaultParamMethod(string $method, string $key, $value)
	{
		$this->params_default[$method][$key] = $value;
	}

	private function uploadImage(int $peer_id, string $local_file_path) 
	{
		$getUploadServerMessages = $this->getUploadServerMessages($peer_id, 'photo');
		$upload_url = $getUploadServerMessages['upload_url'];

		$answer_vk = $this->sendFiles($upload_url, $local_file_path, 'photo');
		$answer_vk = json_decode($answer_vk, true);

		return $this->savePhoto($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash']);
	}

	private function getUploadServerMessages(int $peer_id, string $selector = 'doc')
	{
		$result = null;

		if ($selector == 'doc') {
			$result = $this->api('docs.getMessagesUploadServer', ['type' => 'doc', 'peer_id' => $peer_id]);
		} else if ($selector == 'photo') {
			$result = $this->api('photos.getMessagesUploadServer', ['peer_id' => $peer_id]);
		} else if ($selector == 'audio_message') {
			$result = $this->api('docs.getMessagesUploadServer', ['type' => 'audio_message', 'peer_id' => $peer_id]);
		}

		return $result;
	}

	private function savePhoto($photo, $server, $hash) 
	{
		return $this->api('photos.saveMessagesPhoto', ['photo' => $photo, 'server' => $server, 'hash' => $hash]);
	}

	public function sendImage($peer_id, $local_file_path, array $params = [])
	{
		$upload_file = $this->uploadImage($peer_id, $local_file_path);

		$params['peer_id'] = $peer_id;
		$params['attachment'] = 'photo' . $upload_file[0]['owner_id'] . '_' . $upload_file[0]['id'];

		return $this->sendMessage($params);
	}

	private function sendFiles($url, $local_file_path, $type = 'file')
	{
		curl_setopt_array($this->ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POSTFIELDS => [
				$type => new \CURLFile(\realpath($local_file_path))
			],
			CURLOPT_HTTPHEADER => [
				'Content-Type:multipart/form-data'
			]
		]);

		return curl_exec($this->ch);
	}

	public function buttonText(string $text, string $color = 'default', $payload = null)
	{
		return ['text', $payload, $text, $color];
	}

	public function buttonOpenLink(string $text, $link, $payload = null)
	{
		return ['open_link', $payload, $text, $link];
	    }

	public function generateKeyboard($buttons = [], $inline = false, $one_time = false)
	{
		$keyboard = [];
		$i = 0;

		foreach ($buttons as $button_str) {
			$j = 0;

			foreach ($button_str as $button) {
				$keyboard[$i][$j]['action']['type'] = $button[0];

				if ($button[1] !== null) {
					$keyboard[$i][$j]['action']['payload'] = json_encode($button[1], JSON_UNESCAPED_UNICODE);
				}

				switch ($button[0]) {
					case 'text': {
						$keyboard[$i][$j]['color'] = $button[3];
						$keyboard[$i][$j]['action']['label'] = $button[2];
						break;
					}

					case 'vkpay': {
						$keyboard[$i][$j]['action']['hash'] = 'action={$button[2]}';
						$keyboard[$i][$j]['action']['hash'] .= ($button[3] < 0) ? '&group_id='.$button[3]*-1 : '&user_id={$button[3]}';
						$keyboard[$i][$j]['action']['hash'] .= (isset($button[4])) ? '&amount={$button[4]}' : '';
						$keyboard[$i][$j]['action']['hash'] .= (isset($button[5])) ? '&description={$button[5]}' : '';
						$keyboard[$i][$j]['action']['hash'] .= (isset($button[6])) ? '&data={$button[6]}' : '';
						$keyboard[$i][$j]['action']['hash'] .= '&aid=1';
						break;
					}

					case 'open_app': {
						$keyboard[$i][$j]['action']['label'] = $button[2];
						$keyboard[$i][$j]['action']['app_id'] = $button[3];

						if(isset($button[4])) {
							$keyboard[$i][$j]['action']['owner_id'] = $button[4];
						}

						if(isset($button[5])) {
							$keyboard[$i][$j]['action']['hash'] = $button[5];
						}
						break;
					}
					
					case 'open_link': {
						$keyboard[$i][$j]["action"]["label"] = $button[2];
						$keyboard[$i][$j]["action"]["link"] = $button[3];
						break;
					}
				}

				$j++;
			}
			
			$i++;
		}

		$keyboard = ['one_time' => $one_time, 'buttons' => $keyboard, 'inline' => $inline];

		return json_encode($keyboard, JSON_UNESCAPED_UNICODE);
	}
	
	public function sendMessage(array $params)
	{
		if (array_key_exists('user_ids', $params) && is_array($params['user_ids'])) {
			$params['user_ids'] = implode(', ', $params['user_ids']);
		}

		return $this->api('messages.send', $params);
	}
	
	public function getConversationMembers(int $peer_id, $fields = null, int $group_id = 0)
	{
		$parametrs = [
			'peer_id' => $peer_id
		];

		if (is_array($fields)) {
			$parametrs['fields'] = implode(', ', $fields);
		} else if ($fields !== null) {
			$parametrs['fields'] = $fields;
		}

		if ($group_id !== 0) {
			$parametrs['group_id'] = $group_id;
		}

		return $this->api('messages.getConversationMembers', $parametrs);
	}
	
	public function getUsers($user_ids = null, $fields = null, string $name_case = null)
	{
		$parametrs = [];

		if (is_array($user_ids)) {
			$parametrs['user_ids'] = implode(', ', $user_ids);
		} else if ($user_ids !== null) {
			$parametrs['user_ids'] = $user_ids;
		}

		if (is_array($fields)) {
			$parametrs['fields'] = implode(', ', $fields);
		} else if ($fields !== null) {
			$parametrs['fields'] = $fields;
		}

		if ($name_case !== null) {
			$parametrs['name_case'] = $name_case;
		}

		return $this->api('users.get', $parametrs);
	}
	
	public function isMessagesFromGroupAllowed(int $group_id, int $user_id)
	{
		return $this->api('messages.isMessagesFromGroupAllowed', [
			'group_id' => $group_id,
			'user_id'  => $user_id
		]);
	}


}

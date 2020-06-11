<?php

namespace BuhoyCoder\VK;

class EventType
{
	const CONFIRMATION = 'confirmation';

	/* Сообщения */
	const MESSAGE_NEW   = 'message_new';
	const MESSAGE_EDIT  = 'message_edit';
	const MESSAGE_DENY  = 'message_deny';
	const MESSAGE_REPLY = 'message_reply';
	const MESSAGE_ALLOW = 'message_allow';

	/* Фотографии */
	const PHOTO_NEW             = 'photo_new';
	const PHOTO_COMMENT_NEW     = 'photo_comment_new';
	const PHOTO_COMMENT_EDIT    = 'photo_comment_edit';
	const PHOTO_COMMENT_DELETE  = 'photo_comment_delete';
	const PHOTO_COMMENT_RESTORE = 'photo_comment_restore';

	/* Аудиозаписи */
	const AUDIO_NEW = 'audio_new'; // Добавление аудио

	/* Видеозаписи */
	const VIDEO_NEW             = 'video_new';
	const VIDEO_COMMENT_NEW     = 'video_comment_new';
	const VIDEO_COMMENT_EDIT    = 'video_comment_edit';
	const VIDEO_COMMENT_DELETE  = 'video_comment_delete';
	const VIDEO_COMMENT_RESTORE = 'video_comment_restore';

	/* Записи на стене */
	const WALL_POST_NEW = 'wall_post_new';
	const WALL_REPOST   = 'wall_repost';

	/* Комментарии на стене */
	const WALL_REPLY_NEW     = 'wall_reply_new';
	const WALL_REPLY_EDIT    = 'wall_reply_edit';
	const WALL_REPLY_DELETE  = 'wall_reply_delete';
	const WALL_REPLY_RESTORE = 'wall_reply_restore';

	/* Обсуждения */
	const BOARD_POST_NEW     = 'board_post_new';
	const BOARD_POST_EDIT    = 'board_post_edit';
	const BOARD_POST_DELETE  = 'board_post_delete';
	const BOARD_POST_RESTORE = 'board_post_restore';

	/* Товары */
	const MARKET_ORDER_NEW       = 'market_order_new';
	const MARKET_ORDER_EDIT      = 'market_order_edit';
	const MARKET_COMMENT_NEW     = 'market_comment_new';
	const MARKET_COMMENT_EDIT    = 'market_comment_edit';
	const MARKET_COMMENT_DELETE  = 'market_comment_delete';
	const MARKET_COMMENT_RESTORE = 'market_comment_restore';

	/* Пользователи */
	const USER_BLOCK   = 'user_block';
	const USER_UNBLOCK = 'user_unblock';
	const GROUP_JOIN   = 'group_join';
	const GROUP_LEAVE  = 'group_leave';

	/* Прочее */
	const APP_PAYLOAD           = 'app_payload';
	const LIKE_ADD              = 'like_add';
	const LIKE_REMOVE           = 'like_remove';
	const POLL_VOTE_NEW         = 'poll_vote_new';
	const VKPAY_TRANSACTION     = 'vkpay_transaction';
	const PHOTO_OFFICERS_EDIT   = 'group_officers_edit';
	const GROUP_CHANGE_PHOTO    = 'group_change_photo';
	const GROUP_CHANGE_SETTINGS = 'group_change_settings';
}

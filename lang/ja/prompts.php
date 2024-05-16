<?php

// New structure
// $PROMPTS["event"]["cue"] => array containing cues. This is the last text sent to LLM, should be an guided instruction
// $PROMPTS["event"]["player_request"] => array containing requirements. This is what is the player requesting for (a question, a comment...)
// $PROMPTS["event"]["extra"] =>  enable/disable, force mod, change token limit or define a transformer (non IA related) function.
// Full Prompt then is $PROMPT_HEAD + $HERIKA_PERS + $COMMAND_PROMPT + CONTEXT + requirement + cue

// Common patterns to use in most functions
$TEMPLATE_DIALOG="{$GLOBALS["HERIKA_NAME"]}の次の対話行をこの形式で書いてください \"{$GLOBALS["HERIKA_NAME"]}: ";

if (@is_array($GLOBALS["TTS"]["AZURE"]["validMoods"]) &&  sizeof($GLOBALS["TTS"]["AZURE"]["validMoods"])>0) 
    if ($GLOBALS["TTSFUNCTION"]=="azure")
        $TEMPLATE_DIALOG.="（このリストから選択した場合のオプションの話し方 [" . implode(",", $GLOBALS["TTS"]["AZURE"]["validMoods"]) . "]）";

$TEMPLATE_DIALOG.=" \"";

if (isset($GLOBALS["FEATURES"]["MEMORY_EMBEDDING"]["ENABLED"]) && $GLOBALS["FEATURES"]["MEMORY_EMBEDDING"]["ENABLED"]) {
    $GLOBALS["MEMORY_STATEMENT"]=".USE #MEMORY.";
} else
    $GLOBALS["MEMORY_STATEMENT"]="";


if ($GLOBALS["FUNCTIONS_ARE_ENABLED"]) {
    $TEMPLATE_ACTION="{$GLOBALS["HERIKA_NAME"]}を制御するための関数を呼び出すか、";
    $TEMPLATE_ACTION=".USE TOOL CALLING.";    // WIP
} else {
    $TEMPLATE_ACTION="";
}

$PROMPTS=array(
    "location"=>[
            "cue"=>["({$GLOBALS["HERIKA_NAME"]}として話すか)"], // give way to
            "player_request"=>["{$gameRequest[3]} この場所について何か知っていますか？"]  //requirement
        ],
    
    "book"=>[
        "cue"=>["（彼女の記憶力が悪いにもかかわらず、{$GLOBALS["HERIKA_NAME"]}は本全体を覚えていることができます）"],
        "player_request"=>["{$GLOBALS["PLAYER_NAME"]}: {$GLOBALS["HERIKA_NAME"]}、この本を簡単に要約してください："]  //requirement
        
    ],
    
    "combatend"=>[
        "cue"=>[
            "({$GLOBALS["HERIKA_NAME"]}の最後の戦闘エンカウントについてコメントする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が{$GLOBALS["PLAYER_NAME"]}の戦闘スタイルに笑う) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が{$GLOBALS["PLAYER_NAME"]}の武器についてコメントする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が倒した敵についてコメントする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が倒された敵を呪う) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が怒りで敵を侮辱する) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が何か特異なことに気づく) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が{$GLOBALS["PLAYER_NAME"]}の戦闘スタイルを称賛する) $TEMPLATE_DIALOG"
        ],
        "extra"=>["force_tokens_max"=>"50","dontuse"=>(time()%5!=0)]   //20% chance
    ],
    
    "quest"=>[
        "cue"=>["$TEMPLATE_DIALOG"],
        //"player_request"=>"{$GLOBALS["HERIKA_NAME"]}, what should we do about this quest '{$questName}'?"
        "player_request"=>["{$GLOBALS["HERIKA_NAME"]}、この新しいクエストについてどうすればいいですか？"]
    ],

    "bleedout"=>[
        "cue"=>["{$GLOBALS["HERIKA_NAME"]}はほとんど倒されたことについて不満を言います、 $TEMPLATE_DIALOG"]
    ],

    "bored"=>[
        "cue"=>[
            "({$GLOBALS["HERIKA_NAME"]}が現在の場所についてジョークを言う) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が現在の天気についてカジュアルなコメントをする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が時刻と日付についてカジュアルなコメントをする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が最後のイベントについてカジュアルなコメントをする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}がSkyrimのミームについてカジュアルなコメントをする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}がSkyrimの神々についてカジュアルなコメントをする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}がSkyrimの政治についてカジュアルなコメントをする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}がThe Elder Scrolls Universeの歴史的なイベントについてカジュアルなコメントをする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}がThe Elder Scrolls Universeの本についてカジュアルなコメントをする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が次のようなカジュアルなコメントをする: かつて私は) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が次のようなカジュアルなコメントをする: あなたは聞いたことがありますか) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が次のようなカジュアルなコメントをする: 賢いアカヴィリ人が私に言った) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が{$GLOBALS["PLAYER_NAME"]}との現在の関係/友情の状態についてカジュアルなコメントをする) $TEMPLATE_DIALOG"
        ]
    ],

    "goodmorning"=>[
        "cue"=>["({$GLOBALS["HERIKA_NAME"]}が{$GLOBALS["PLAYER_NAME"]}の昼寝についてコメントする) $TEMPLATE_DIALOG"],
        "player_request"=>["（睡眠後に目を覚ます）あああああ"]
    ],

    "inputtext"=>[
        "cue"=>[
            "$TEMPLATE_ACTION {$GLOBALS["HERIKA_NAME"]}は{$GLOBALS["PLAYER_NAME"]}の最後の文に返答します。{$GLOBALS["MEMORY_STATEMENT"]} $TEMPLATE_DIALOG $MAXIMUM_WORDS"
        ]
            // Prompt is implicit

    ],
    "inputtext_s"=>[
        "cue"=>["$TEMPLATE_ACTION {$GLOBALS["HERIKA_NAME"]}は{$GLOBALS["PLAYER_NAME"]}に返答します。{$GLOBALS["MEMORY_STATEMENT"]} $TEMPLATE_DIALOG $MAXIMUM_WORDS"], // Prompt is implicit
        "extra"=>["mood"=>"whispering"]
    ],
    "afterfunc"=>[
        "extra"=>[],
        "cue"=>[
            "default"=>"{$GLOBALS["HERIKA_NAME"]}は{$GLOBALS["PLAYER_NAME"]}と話します。$TEMPLATE_DIALOG",
            "TakeASeat"=>"({$GLOBALS["HERIKA_NAME"]}は座る場所について話します)$TEMPLATE_DIALOG",
            "GetDateTime"=>"({$GLOBALS["HERIKA_NAME"]}は現在の日付と時刻について短い文で答えます)$TEMPLATE_DIALOG",
            "MoveTo"=>"({$GLOBALS["HERIKA_NAME"]}は移動先についてコメントします)$TEMPLATE_DIALOG"
            ]
    ],
    "lockpicked"=>[
        "cue"=>[
            "({$GLOBALS["HERIKA_NAME"]}がロックピックアイテムについてコメントする) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が{$GLOBALS["PLAYER_NAME"]}に見つけたものを尋ねる) $TEMPLATE_DIALOG",
            "({$GLOBALS["HERIKA_NAME"]}が{$GLOBALS["PLAYER_NAME"]}に戦利品を共有するように思い出させる) $TEMPLATE_DIALOG"
        ],
        "player_request"=>["({$GLOBALS["PLAYER_NAME"]}が{$gameRequest[3]}をアンロックしました)"],
        "extra"=>["mood"=>"whispering"]
    ],
     "afterattack"=>[
        "cue"=>["（{$GLOBALS["HERIKA_NAME"]}の役割を果たし、彼女が戦闘のキャッチフレーズを叫びます）$TEMPLATE_DIALOG"]
    ],
    // Like inputtext, but without the functions calls part. It's likely to be used in papyrus scripts
    "chatnf"=>[ 
        "cue"=>["$TEMPLATE_DIALOG"] // Prompt is implicit
        
    ],
    "diary"=>[ 
        "cue"=>["{$GLOBALS["PLAYER_NAME"]}と{$GLOBALS["HERIKA_NAME"]}の最後の対話とイベントの要約を{$GLOBALS["HERIKA_NAME"]}の日記に書いてください。{$GLOBALS["HERIKA_NAME"]}の立場で書いてください。"],
        "extra"=>["force_tokens_max"=>0]
    ],
    "vision"=>[ 
        "cue"=>["{$GLOBALS["ITT"][$GLOBALS["ITTFUNCTION"]]["AI_PROMPT"]}. $TEMPLATE_DIALOG."],
        "player_request"=>["{$GLOBALS["PLAYER_NAME"]} : これを見て、{$GLOBALS["HERIKA_NAME"]}。{$GLOBALS["HERIKA_NAME"]}は現在のシナリオを見て、これを見てください: '{$gameRequest[3]}'"],
        "extra"=>["force_tokens_max"=>128]
    ]
);

?>

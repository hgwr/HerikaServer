<?php



function TkGetCostPerThousandInputTokens($CURRENT_MODEL)
{
    $costPerThousandTokens = 0;
    if ($CURRENT_MODEL == 'gpt-3.5-turbo') {
        $costPerThousandTokens = 0.0015;
    } elseif ($CURRENT_MODEL == 'gpt-3.5-turbo-16k') {
        $costPerThousandTokens = 0.003;
    } elseif ($CURRENT_MODEL == 'gpt-3.5-turbo-0613') {
        $costPerThousandTokens = 0.0015;
    } elseif ($CURRENT_MODEL == 'gpt-3.5-turbo-16k-0613') {
        $costPerThousandTokens = 0.003;
    } elseif ($CURRENT_MODEL == 'gpt-4') {
        $costPerThousandTokens = 0.03;
    } elseif ($CURRENT_MODEL == 'gpt-4-0613') {
        $costPerThousandTokens = 0.03;
    } elseif ($CURRENT_MODEL == 'gpt-4-32k') {
        $costPerThousandTokens = 0.06;
    } elseif ($CURRENT_MODEL == 'gpt-4-32k-0613') {
        $costPerThousandTokens = 0.06;
    } else {
        trigger_error("Cannot tokenize - unrecognized model {$CURRENT_MODEL}",E_USER_WARNING);
        $costPerThousandTokens = 0; // model unknown
    }

    return $costPerThousandTokens;
}

function TkGetCostPerThousandOutputTokens($CURRENT_MODEL)
{
    $costPerThousandTokens = 0;
    if ($CURRENT_MODEL == 'gpt-3.5-turbo') {
        $costPerThousandTokens = 0.002;
    } elseif ($CURRENT_MODEL == 'gpt-3.5-turbo-16k') {
        $costPerThousandTokens = 0.004;
    } elseif ($CURRENT_MODEL == 'gpt-3.5-turbo-0613') {
        $costPerThousandTokens = 0.002;
    } elseif ($CURRENT_MODEL == 'gpt-3.5-turbo-16k-0613') {
        $costPerThousandTokens = 0.004;
    } elseif ($CURRENT_MODEL == 'gpt-4') {
        $costPerThousandTokens = 0.06;
    } elseif ($CURRENT_MODEL == 'gpt-4-0613') {
        $costPerThousandTokens = 0.06;
    } elseif ($CURRENT_MODEL == 'gpt-4-32k') {
        $costPerThousandTokens = 0.12;
    } elseif ($CURRENT_MODEL == 'gpt-4-32k-0613') {
        $costPerThousandTokens = 0.12;
    } else {
        trigger_error("Cannot tokenize - unrecognized model {$CURRENT_MODEL}",E_USER_WARNING);
        $costPerThousandTokens = 0; // model unknown
    }

    return $costPerThousandTokens;
}


function TkInsertAndCalcTotals($table, $data)
    {
        
        
        // Fetch the last row
        $latestRowQuery = "SELECT * FROM $table ORDER BY ROWID DESC LIMIT 1";
        $latestRowResult = $GLOBALS["db"]->fetchAll($latestRowQuery);

        if (!empty($latestRowResult)) {
            // If the table is not empty
            $latestRow = $latestRowResult[0];

            // Calculate totals
            $data['total_tokens_so_far'] = $latestRow['total_tokens_so_far'] + $data['input_tokens'] + $data['output_tokens'];
            $data['total_cost_so_far_USD'] = $latestRow['total_cost_so_far_USD'] + $data['cost_USD'];
        } else {
            // If the table is empty
            $data['total_tokens_so_far'] = $data['input_tokens'] + $data['output_tokens'];
            $data['total_cost_so_far_USD'] = $data['cost_USD'];
        }

        // Insert new row
       $GLOBALS["db"]->execQuery("INSERT INTO $table (" . implode(",", array_keys($data)) . ") VALUES ('" . implode("','", $data) . "')");
    }

    
function TkTokenizePrompt($jsonEncodedData, $CURRENT_MODEL)
{


    $costPerThousandTokens = TkGetCostPerThousandOutputTokens($CURRENT_MODEL);
    // connect to local Python server servicing tokenizing requests
    $tokenizer_url = 'http://127.0.0.1:8090';
    $tokenizer_headers = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $jsonEncodedData,
            'timeout' => 2
        )
    );
    $tokenizer_context = stream_context_create($tokenizer_headers);
    $tokenizer_buffer = file_get_contents($tokenizer_url, false, $tokenizer_context);
    if ($tokenizer_buffer !== false) {
        $tokenizer_buffer = trim($tokenizer_buffer);
        if (ctype_digit($tokenizer_buffer)) { // make sure the response from tokenizer is a number (num of tokens)
            $numTokens = intval($tokenizer_buffer);
            $cost = $numTokens * $costPerThousandTokens * 0.001;
            TkInsertAndCalcTotals(
                'openai_token_count',
                array(
                    'input_tokens' => $tokenizer_buffer,
                    'output_tokens' => '0',
                    'cost_USD' => $cost,
                    'localts' => time(),
                    'datetime' => date("Y-m-d H:i:s"),
                    'model' => $CURRENT_MODEL
                )
            );
        }
    } else {
        trigger_error("error: tokenizer buf false",E_USER_WARNING);
    }


}

function TkTokenizeResponse($numOutputTokens, $CURRENT_MODEL)
{
    

    if (isset($CURRENT_MODEL)) {
        $costPerThousandTokens = TkGetCostPerThousandOutputTokens($CURRENT_MODEL);
        $cost = $numOutputTokens * $costPerThousandTokens * 0.001;
        TkInsertAndCalcTotals(
            'openai_token_count',
            array(
                'input_tokens' => '0',
                'output_tokens' => $numOutputTokens,
                'cost_USD' => $cost,
                'localts' => time(),
                'datetime' => date("Y-m-d H:i:s"),
                'model' => $CURRENT_MODEL
            )
        );
    }
}

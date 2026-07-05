<?php
$file = 'transactions.php';
$content = file_get_contents($file);

// Update HTML table headers
$search1 = "                                        <th>Amount</th>
                                        <th>Status</th>";
$replace1 = "                                        <th>Gross Amount</th>
                                        <th>Platform Fee</th>
                                        <th>Net Earnings</th>
                                        <th>Status</th>";
$content = str_replace($search1, $replace1, $content);

// Update Datatables columns
$search2 = "                  {
                      data: 'amount',
                      name: 'amount'
                  },
                  {
                      data: 'status',
                      name: 'status'
                  },";
$replace2 = "                  {
                      data: 'amount',
                      name: 'amount'
                  },
                  {
                      data: 'fee',
                      name: 'fee'
                  },
                  {
                      data: 'net',
                      name: 'net'
                  },
                  {
                      data: 'status',
                      name: 'status'
                  },";
$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Updated transactions.php\n";

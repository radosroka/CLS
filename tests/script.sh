#~/bin/bash

#set -x


cp ../cls.php ./cls.php
cp ~/jexamxml.jar ./
./_stud_tests.sh

for  i in `seq 1 13` ;
do
if [ $i -lt 10 ]; then
	java -jar ./jexamxml.jar test0$i.out ref-out/test0$i.out
else 
	java -jar ./jexamxml.jar test$i.out ref-out/test$i.out
fi
done

rm -rf ./cls.php

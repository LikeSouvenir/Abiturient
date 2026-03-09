import fs from "node:fs"

const fileName = "./db/datas/006_datas_establishments.sql"

const resultData = []
const addresses = []
const phones = []

const establishment_path = "./db/datas/new_006_datas_establishments.sql"
const addresses_path = "./db/datas/new_009_datas_addresses.sql"
const phones_path  = "./db/datas/new_010_datas_phones.sql"
const est_base = "INSERT INTO establishments (id, name, logo_path, latitude, longitude, website, created_at) VALUES "
const addresses_base = "INSERT INTO addresses (id, establishment_id, address, admissions_committee, created_at) VALUES "
const phones_base = "INSERT INTO phones (id, establishment_id, phone, admissions_committee, created_at) VALUES "


async function write(preambula, arr, path) {
  let input = preambula;
  const len = arr.length
  console.log(len)
  for (let i = 0; i < len; ++i) {
    input += '\n\(' 
    let elem = arr[i]
    for (let j = 0; j < elem.length; ++j) {
      input += "'" + elem[j] + "'"
      if (j !== elem.length - 1) input += ','
    };
    if (i === len - 1) input += '\);'
    else input += '\),';
  }

  try {
    fs.writeFileSync(path, input)
    console.log("success")
  } catch (err) {
    console.log(err)
  }
}

async function read() {
  fs.readFile(fileName, 'utf8', (err, data) => {
    if (err) {
      console.log(err)
    }
    const splitInd = data.indexOf('VALUES')
    const res = data.slice(splitInd + 6, data.length - 4).replaceAll('(', "").replace('\n', "").split('\),\n')

    let key = 1;
    for (let elem of res) {
      let item = elem.split("', '")
      item[0] = item[0].replace(" \'", '')
      const idx = (item[0].indexOf(','))
      item[0] = [item[0].slice(0, idx), item[0].slice(idx + 1)]
      item = item.flat()
      item[8] = item[8].replace("'", '')

      addresses.push([
        key, item[0], item[3], 0, item[8] 
      ])
      phones.push([
        key, item[0], item[6], 0, item[8]
      ])
      
      item[3] = null;
      item[6] = null;

      ++key;
      resultData.push(item.filter(e => e !== null))
    }
    console.log("read--read--read--read")
  })
  setTimeout(async () => {
    await write(est_base, resultData, establishment_path)
    await write(addresses_base, addresses, addresses_path)
    await write(phones_base, phones, phones_path)
  }, 1000)
}

read()

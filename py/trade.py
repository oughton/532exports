import csv

def read_csv(path):
    result = {}

    with open(path, 'rb') as f:
        reader = csv.reader(f)
        for row in reader:
            year = 2000
            i = 0
            country = None
            vals = {}
            for val in row:
                if i == 0:
                    # Country
                    country = val
                    i += 1
                else:
                    # Value
                    vals[str(year)] = int(val)
                    year += 1

            result[country] = vals

    return result
